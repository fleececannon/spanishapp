<?php

namespace App\Livewire\Admin;

use App\Enums\Direction;
use App\Enums\ReviewResult;
use App\Models\Kid;
use App\Models\ReviewState;
use App\Services\Srs\DueCardService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Progress')]
class Progress extends Component
{
    /** Interval (days) at which a card counts as "learned". */
    private const LEARNED_INTERVAL = 7;

    public function render(DueCardService $dueCards)
    {
        $stats = Kid::orderBy('name')->get()->map(fn (Kid $kid) => [
            'kid' => $kid,
            // The two directions are separate decks, so each gets its own read.
            'decks' => collect(Direction::cases())->map(function (Direction $direction) use ($kid, $dueCards) {
                // onActiveCards: retired cards keep their history, but it must not count here.
                $states = ReviewState::where('kid_id', $kid->id)->onActiveCards($direction);
                $seen = (clone $states)->count();
                $learned = (clone $states)->where('interval_days', '>=', self::LEARNED_INTERVAL)->count();

                return [
                    'direction' => $direction,
                    'seen' => $seen,
                    'learned' => $learned,
                    'needs_work' => (clone $states)->where('last_result', ReviewResult::NeedsWork->value)->count(),
                    'due_now' => $dueCards->dueCount($kid, $direction),
                    'mastery' => $seen > 0 ? round($learned / $seen * 100) : 0,
                ];
            }),
        ]);

        return view('livewire.admin.progress', ['stats' => $stats]);
    }
}
