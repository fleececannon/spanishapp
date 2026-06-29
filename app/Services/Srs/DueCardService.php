<?php

namespace App\Services\Srs;

use App\Models\Card;
use App\Models\Kid;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds a kid's review queue: every card already due today, plus up to
 * daily_new_card_pace brand-new cards they've never seen. This is the only
 * place the new-card pace is enforced.
 */
class DueCardService
{
    /** @return Collection<int, Card> */
    public function queueFor(Kid $kid): Collection
    {
        $today = Carbon::today()->toDateString();

        // Cards with an existing schedule that's due.
        $due = Card::active()
            ->whereHas('reviewStates', fn ($q) => $q
                ->where('kid_id', $kid->id)
                ->whereDate('due', '<=', $today))
            ->get();

        // Fresh cards this kid has never seen, capped by their pace.
        $startingEase = (float) (Setting::get('srs_tuning', [])['starting_ease'] ?? 2.5);

        $new = Card::active()
            ->whereDoesntHave('reviewStates', fn ($q) => $q->where('kid_id', $kid->id))
            ->limit($kid->daily_new_card_pace)
            ->get();

        return $due->concat($new)->unique('id')->values();
    }

    /** Count of cards waiting for this kid right now. */
    public function dueCount(Kid $kid): int
    {
        return $this->queueFor($kid)->count();
    }
}
