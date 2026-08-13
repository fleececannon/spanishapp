<?php

namespace App\Livewire;

use App\Enums\ReviewResult;
use App\Models\Card;
use App\Models\Kid;
use App\Models\ReviewState;
use App\Models\Verb;
use App\Models\Word;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    /** Interval (days) at which a card counts as "mastered" — same bar the kid dashboard uses. */
    private const MASTERED_INTERVAL = 7;

    #[Url]
    public ?int $kid = null;

    public function render()
    {
        $kids = Kid::orderBy('name')->get();

        $selected = $kids->firstWhere('id', $this->kid) ?? $kids->first();
        $this->kid = $selected?->id;

        return view('livewire.dashboard', [
            'kids' => $kids,
            'selected' => $selected,
            ...($selected ? $this->metricsFor($selected) : []),
        ]);
    }

    /** @return array<string, mixed> */
    private function metricsFor(Kid $kid): array
    {
        $today = Carbon::today();
        $cards = Card::active()->get(['id', 'spanish', 'english', 'uses_concepts']);
        $states = ReviewState::where('kid_id', $kid->id)->get()->keyBy('card_id');

        $seen = $states->count();
        $masteredCards = $states->where('interval_days', '>=', self::MASTERED_INTERVAL)->count();
        $dueToday = $states->filter(fn (ReviewState $s) => $s->due->lte($today))->count();
        $needsWork = $states->where('last_result', ReviewResult::NeedsWork)->count();
        $reviewedToday = $states->filter(fn (ReviewState $s) => $s->last_reviewed?->isToday())->count();

        $totalReps = (int) $states->sum('reps');
        $totalLapses = (int) $states->sum('lapses');

        // Reviews landing today (incl. overdue) and over the next 13 days.
        $upcoming = collect(range(0, 13))->map(function (int $offset) use ($states, $today) {
            $date = $today->copy()->addDays($offset);
            $count = $states->filter(fn (ReviewState $s) => $offset === 0
                ? $s->due->lte($date)
                : $s->due->isSameDay($date))->count();

            return ['dow' => $date->format('D'), 'day' => $date->format('j'), 'count' => $count];
        });

        return [
            'dueToday' => $dueToday,
            'newCount' => max(0, $cards->count() - $seen),
            'seen' => $seen,
            'masteredCards' => $masteredCards,
            'needsWork' => $needsWork,
            'reviewedToday' => $reviewedToday,
            'accuracy' => $totalReps > 0 ? (int) round(($totalReps - $totalLapses) / $totalReps * 100) : null,
            'totalCards' => $cards->count(),
            'masteryPct' => $cards->count() > 0 ? (int) round($masteredCards / $cards->count() * 100) : 0,
            'upcoming' => $upcoming,
            'upcomingMax' => max(1, $upcoming->max('count')),
            'concepts' => $this->conceptMastery($cards, $states),
            'trouble' => $this->troubleCards($cards, $states),
        ];
    }

    /**
     * Group every unlocked verb and word by how well the kid knows it:
     * mastered = every card they've seen using it is at a 7-day-plus interval;
     * learning = they've seen at least one card using it; new = untouched.
     *
     * @return array{verbs: array<string, list<string>>, words: array<string, list<string>>}
     */
    private function conceptMastery($cards, $states): array
    {
        $cardsByConcept = [];
        foreach ($cards as $card) {
            foreach ($card->uses_concepts ?? [] as $use) {
                if (in_array($use['type'] ?? null, ['verb', 'word'], true) && isset($use['id'])) {
                    $cardsByConcept["{$use['type']}:{$use['id']}"][] = $card->id;
                }
            }
        }

        $statusOf = function (string $key) use ($cardsByConcept, $states): string {
            $seenStates = collect($cardsByConcept[$key] ?? [])
                ->map(fn (int $cardId) => $states->get($cardId))
                ->filter();

            if ($seenStates->isEmpty()) {
                return 'new';
            }

            return $seenStates->every(fn (ReviewState $s) => $s->interval_days >= self::MASTERED_INTERVAL)
                ? 'mastered'
                : 'learning';
        };

        $groups = ['verbs' => ['mastered' => [], 'learning' => [], 'new' => []],
            'words' => ['mastered' => [], 'learning' => [], 'new' => []]];

        foreach (Verb::unlocked()->orderBy('spanish')->get(['id', 'spanish']) as $verb) {
            $groups['verbs'][$statusOf("verb:{$verb->id}")][] = $verb->spanish;
        }
        foreach (Word::unlocked()->orderBy('spanish')->get(['id', 'spanish']) as $word) {
            $groups['words'][$statusOf("word:{$word->id}")][] = $word->spanish;
        }

        return $groups;
    }

    /** The five cards this kid trips on the most. */
    private function troubleCards($cards, $states)
    {
        return $states
            ->filter(fn (ReviewState $s) => $s->lapses > 0)
            ->sortByDesc('lapses')
            ->take(5)
            ->map(function (ReviewState $s) use ($cards) {
                $card = $cards->firstWhere('id', $s->card_id);

                return $card ? [
                    'spanish' => $card->spanish,
                    'english' => $card->english,
                    'lapses' => $s->lapses,
                    'reps' => $s->reps,
                ] : null;
            })
            ->filter()
            ->values();
    }
}
