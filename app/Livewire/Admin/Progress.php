<?php

namespace App\Livewire\Admin;

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
        $stats = Kid::orderBy('name')->get()->map(function (Kid $kid) use ($dueCards) {
            $states = ReviewState::where('kid_id', $kid->id);
            $seen = (clone $states)->count();
            $learned = (clone $states)->where('interval_days', '>=', self::LEARNED_INTERVAL)->count();
            $needsWork = (clone $states)->where('last_result', ReviewResult::NeedsWork->value)->count();

            return [
                'kid' => $kid,
                'seen' => $seen,
                'learned' => $learned,
                'needs_work' => $needsWork,
                'due_now' => $dueCards->dueCount($kid),
                'mastery' => $seen > 0 ? round($learned / $seen * 100) : 0,
            ];
        });

        return view('livewire.admin.progress', ['stats' => $stats]);
    }
}
