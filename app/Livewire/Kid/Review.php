<?php

namespace App\Livewire\Kid;

use App\Enums\ReviewResult;
use App\Models\Card;
use App\Models\ReviewState;
use App\Models\Setting;
use App\Services\Claude\AnswerGrader;
use App\Services\Claude\ClaudeException;
use App\Services\Srs\DueCardService;
use App\Services\Srs\Scheduler;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.kid')]
#[Title('Practice')]
class Review extends Component
{
    /** Remaining card ids to review this session (FIFO). */
    public array $queue = [];

    public ?int $currentId = null;

    public string $answer = '';

    /** "Out loud" mode: reveal + tap correct/incorrect instead of typing. */
    public bool $outLoud = false;

    public bool $revealed = false;

    public bool $showResult = false;

    public bool $lastPassed = false;

    public ?string $acceptedEnglish = null;

    public ?string $nudge = null;

    public bool $done = false;

    public function mount(DueCardService $dueCards): void
    {
        $this->queue = $dueCards->queueFor($this->kid())->pluck('id')->all();
        $this->advance();
    }

    public function submit(AnswerGrader $grader, Scheduler $scheduler): void
    {
        if ($this->currentId === null || trim($this->answer) === '' || $this->showResult) {
            return;
        }

        $card = Card::find($this->currentId);
        if (! $card) {
            $this->advance();

            return;
        }

        try {
            $result = $grader->grade($card, $this->answer);
        } catch (ClaudeException $e) {
            $this->addError('answer', 'Could not check that just now — try again.');

            return;
        }

        $state = $this->reviewStateFor($card->id);
        $scheduler->apply($state, $result->verdict);

        $this->showResult = true;
        $this->lastPassed = $result->passed();
        $this->acceptedEnglish = $result->acceptedEnglish;
        $this->nudge = $result->nudge;

        // A miss comes back later this session.
        if (! $result->passed()) {
            $this->queue[] = $card->id;
        }
    }

    public function reveal(): void
    {
        $this->revealed = true;
    }

    /** Out-loud mode: a grown-up marks the spoken answer right or wrong — no AI call. */
    public function mark(Scheduler $scheduler, bool $correct): void
    {
        if ($this->currentId === null || $this->showResult) {
            return;
        }

        $card = Card::find($this->currentId);
        if (! $card) {
            $this->advance();

            return;
        }

        $verdict = $correct ? ReviewResult::Pass : ReviewResult::NeedsWork;
        $scheduler->apply($this->reviewStateFor($card->id), $verdict);

        // A miss comes back later this session.
        if (! $correct) {
            $this->queue[] = $card->id;
        }

        // No result screen out loud — the grown-up already saw the answer
        // and made the call. Straight to the next card.
        $this->advance();
    }

    public function next(): void
    {
        $this->advance();
    }

    private function advance(): void
    {
        $this->reset(['answer', 'revealed', 'showResult', 'lastPassed', 'acceptedEnglish', 'nudge']);

        if (empty($this->queue)) {
            $this->currentId = null;
            $this->done = true;

            return;
        }

        $this->currentId = array_shift($this->queue);
    }

    private function reviewStateFor(int $cardId): ReviewState
    {
        $state = ReviewState::firstOrNew([
            'kid_id' => $this->kid()->id,
            'card_id' => $cardId,
        ]);

        if (! $state->exists) {
            $tuning = Setting::get('srs_tuning', []);
            $state->ease = (float) ($tuning['starting_ease'] ?? 2.5);
            $state->interval_days = 0;
            $state->reps = 0;
            $state->lapses = 0;
            $state->due = Carbon::today();
        }

        return $state;
    }

    private function kid()
    {
        return auth('kid')->user();
    }

    public function logout()
    {
        auth('kid')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return $this->redirectRoute('kid.login', navigate: true);
    }

    public function render()
    {
        return view('livewire.kid.review', [
            'card' => $this->currentId ? Card::find($this->currentId) : null,
            'remaining' => count($this->queue) + ($this->currentId && ! $this->showResult ? 1 : 0),
        ]);
    }
}
