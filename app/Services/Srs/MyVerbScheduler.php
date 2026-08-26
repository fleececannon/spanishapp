<?php

namespace App\Services\Srs;

use App\Enums\ReviewResult;
use App\Models\MyVerb;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * The kids' Scheduler math applied to the parent's verb rows. Deliberately a
 * copy rather than a refactor of Scheduler (which is typed to ReviewState):
 * same srs_tuning settings, so tuning changes affect both.
 */
class MyVerbScheduler
{
    public function apply(MyVerb $verb, ReviewResult $result): MyVerb
    {
        $tuning = Setting::get('srs_tuning', []);
        $startingInterval = (int) ($tuning['starting_interval'] ?? 1);
        $startingEase = (float) ($tuning['starting_ease'] ?? 2.5);
        $missPenalty = (float) ($tuning['miss_penalty'] ?? 0.2);
        $minEase = (float) ($tuning['min_ease'] ?? 1.3);

        $ease = (float) ($verb->ease ?? $startingEase);
        $verb->ease = $verb->ease ?? $startingEase;
        $today = Carbon::today();

        if ($result === ReviewResult::Pass) {
            $interval = $verb->reps < 1
                ? $startingInterval
                : max(1, (int) round($verb->interval_days * $ease));

            $verb->interval_days = $interval;
            $verb->reps = $verb->reps + 1;
            $verb->due = $today->copy()->addDays($interval);
        } else {
            $verb->interval_days = 0;
            $verb->ease = max($minEase, round($ease - $missPenalty, 2));
            $verb->lapses = $verb->lapses + 1;
            $verb->due = $today;
        }

        $verb->last_result = $result->value;
        $verb->last_reviewed = now();
        $verb->save();

        return $verb;
    }
}
