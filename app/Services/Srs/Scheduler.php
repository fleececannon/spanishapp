<?php

namespace App\Services\Srs;

use App\Enums\ReviewResult;
use App\Models\ReviewState;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * SM-2-lite. The grown-up's verdict on the spoken answer (pass / needs_work)
 * is the only input. Pass grows the interval by the ease factor; a miss resets
 * the card to "due today" and nudges ease down.
 */
class Scheduler
{
    public function apply(ReviewState $state, ReviewResult $result): ReviewState
    {
        $tuning = Setting::get('srs_tuning', []);
        $startingInterval = (int) ($tuning['starting_interval'] ?? 1);
        $startingEase = (float) ($tuning['starting_ease'] ?? 2.5);
        $missPenalty = (float) ($tuning['miss_penalty'] ?? 0.2);
        $minEase = (float) ($tuning['min_ease'] ?? 1.3);

        $ease = (float) ($state->ease ?? $startingEase);
        $today = Carbon::today();

        if ($result === ReviewResult::Pass) {
            $interval = $state->reps < 1
                ? $startingInterval
                : max(1, (int) round($state->interval_days * $ease));

            $state->interval_days = $interval;
            $state->reps = $state->reps + 1;
            $state->due = $today->copy()->addDays($interval);
        } else {
            // Miss: comes back this session (due today), small ease penalty.
            $state->interval_days = 0;
            $state->ease = max($minEase, round($ease - $missPenalty, 2));
            $state->lapses = $state->lapses + 1;
            $state->due = $today;
        }

        $state->last_result = $result;
        $state->last_reviewed = now();
        $state->save();

        return $state;
    }
}
