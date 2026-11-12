<?php

namespace App\Livewire\Kid;

use App\Enums\Direction;
use App\Enums\ReviewResult;
use App\Models\Card;
use App\Models\ReviewState;
use App\Models\Setting;
use App\Services\Srs\DueCardService;
use App\Services\Srs\Scheduler;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * The review loop is spoken, not typed: the kid says the answer out loud, the
 * card is revealed, and a grown-up marks it right or wrong. That verdict is
 * the only input to the schedule.
 */
#[Layout('components.layouts.kid')]
#[Title('Practice')]
class Review extends Component
{
    /** Which deck this session is drilling (a Direction value). */
    #[Url]
    public string $direction = Direction::EsToEn->value;

    /** Remaining card ids to review this session (FIFO). */
    public array $queue = [];

    public ?int $currentId = null;

    public bool $revealed = false;

    public bool $done = false;

    public function mount(DueCardService $dueCards): void
    {
        $this->direction = $this->deck()->value;
        $this->queue = $dueCards->queueFor($this->kid(), $this->deck())->pluck('id')->all();
        $this->advance();
    }

    public function reveal(): void
    {
        $this->revealed = true;
    }

    /** A grown-up marks the spoken answer right or wrong. */
    public function mark(Scheduler $scheduler, bool $correct): void
    {
        if ($this->currentId === null) {
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

        $this->advance();
    }

    /**
     * Pass on a card without judging it: no schedule change, no miss, no rep.
     * The card keeps whatever due date it already had (a new card stays new),
     * so it simply comes back in a later session. It does not return later in
     * this session — skipping has to actually get you past it.
     */
    public function skip(): void
    {
        if ($this->currentId === null) {
            return;
        }

        $this->advance();
    }

    private function advance(): void
    {
        $this->reset(['revealed']);

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

    private function deck(): Direction
    {
        return Direction::fromInput($this->direction);
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
            'deck' => $this->deck(),
            'remaining' => count($this->queue) + ($this->currentId ? 1 : 0),
        ]);
    }
}
