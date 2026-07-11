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

        // Reviews that have come due. Shuffled per session so the order isn't
        // predictable — but they always come before anything new.
        $due = Card::active()
            ->join('review_states', 'review_states.card_id', '=', 'cards.id')
            ->where('review_states.kid_id', $kid->id)
            ->whereDate('review_states.due', '<=', $today)
            ->select('cards.*')
            ->get()
            ->shuffle();

        // Every card this kid has never seen — also shuffled.
        $new = Card::active()
            ->whereDoesntHave('reviewStates', fn ($q) => $q->where('kid_id', $kid->id))
            ->get()
            ->shuffle();

        return $due->concat($new)->unique('id')->values();
    }

    /** Count of cards waiting for this kid right now. */
    public function dueCount(Kid $kid): int
    {
        return $this->queueFor($kid)->count();
    }
}
