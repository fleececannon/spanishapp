<?php

namespace App\Livewire\Admin;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Models\Card;
use App\Models\ReviewState;
use App\Models\Verb;
use App\Services\Claude\CardGenerator;
use App\Services\Claude\ClaudeException;
use App\Services\Vocab\VocabCardService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Generate cards')]
class Generation extends Component
{
    #[Validate('required|integer|min:1|max:20')]
    public int $count = 5;

    #[Validate('nullable|string|max:200')]
    public ?string $emphasis = null;

    public function generate(CardGenerator $generator): void
    {
        $this->validate();

        try {
            $created = $generator->generate($this->count, $this->emphasis ?: null);
        } catch (ClaudeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->toastCreated($created);
    }

    /** Retire cards the kids keep missing, then backfill the same number. Progress on survivors is untouched. */
    public function refresh(CardGenerator $generator): void
    {
        // Vocab cards are pinned by their checkbox — SRS repeats them; refresh never culls them.
        $weakIds = Card::active()
            ->where('source', '!=', CardSource::Vocab->value)
            ->withSum('reviewStates as total_lapses', 'lapses')
            ->withSum('reviewStates as total_reps', 'reps')
            ->withCount('reviewStates')
            ->get()
            ->filter(fn ($c) => $c->review_states_count > 0 && (int) $c->total_lapses > (int) $c->total_reps)
            ->pluck('id');

        if ($weakIds->isEmpty()) {
            Flux::toast(variant: 'warning', text: 'No weak cards to refresh right now.');

            return;
        }

        Card::whereIn('id', $weakIds)->update(['status' => CardStatus::Retired]);

        try {
            $created = $generator->generate($weakIds->count());
        } catch (ClaudeException $e) {
            Flux::toast(variant: 'danger', text: 'Retired '.$weakIds->count().' weak cards, but backfill failed: '.$e->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: "Retired {$weakIds->count()} weak card(s) and added {$created} fresh one(s).");
    }

    /** Nuclear: wipe every card AND all kids' schedules, then build a fresh batch. */
    public function rebuild(CardGenerator $generator, VocabCardService $vocabCards): void
    {
        $this->validate();

        ReviewState::query()->delete();
        Card::query()->delete();

        // The vocab-card checkboxes still say these cards should exist — bring them back.
        $vocabCards->resyncAll();

        try {
            $created = $generator->generate($this->count);
        } catch (ClaudeException $e) {
            Flux::toast(variant: 'danger', text: 'Deck cleared, but generation failed: '.$e->getMessage());

            return;
        }

        Flux::toast(variant: 'success', text: "Rebuilt the deck — {$created} new card(s). All schedules were reset.");
    }

    private function toastCreated(int $created): void
    {
        Flux::toast(
            variant: $created > 0 ? 'success' : 'warning',
            text: $created > 0
                ? "Added {$created} card".($created === 1 ? '' : 's').'.'
                : 'No cards passed the fence — try again or adjust the unlocked set.',
        );
    }

    public function render()
    {
        return view('livewire.admin.generation', [
            'recent' => Card::active()->latest()->limit(15)->get(),
            'activeCount' => Card::active()->count(),
            'unlockedVerbs' => Verb::unlocked()->count(),
        ]);
    }
}
