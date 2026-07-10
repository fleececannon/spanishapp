<?php

namespace App\Services\Srs;

use App\Models\Card;
use App\Models\Kid;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds a kid's review queue: every active card that is either due for review
 * or has never been seen. The whole deck is available from day one; spaced
 * repetition pushes passed cards into the future, and anything overdue piles
 * back up. New cards join the queue as soon as they are created.
 */
class DueCardService
{
    /** @return Collection<int, Card> */
    public function queueFor(Kid $kid): Collection
    {
        $today = Carbon::today()->toDateString();

        // Reviews that have come due — most overdue first.
        $due = Card::active()
            ->join('review_states', 'review_states.card_id', '=', 'cards.id')
            ->where('review_states.kid_id', $kid->id)
            ->whereDate('review_states.due', '<=', $today)
            ->orderBy('review_states.due')
            ->select('cards.*')
            ->get();

        // Every card this kid has never seen — oldest first.
        $new = Card::active()
            ->whereDoesntHave('reviewStates', fn ($q) => $q->where('kid_id', $kid->id))
            ->orderBy('id')
            ->get();

        return $due->concat($new)->unique('id')->values();
    }

    /** Count of cards waiting for this kid right now. */
    public function dueCount(Kid $kid): int
    {
        return $this->queueFor($kid)->count();
    }
}
