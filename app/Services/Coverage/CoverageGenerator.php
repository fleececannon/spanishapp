<?php

namespace App\Services\Coverage;

use App\Services\Claude\CardGenerator;

/**
 * Fills coverage gaps by repeatedly asking the generator for sentences that
 * cover the specific missing slots. Bounded per call (so a single request stays
 * responsive) and resumable — calling it again continues from the current gaps.
 */
class CoverageGenerator
{
    public function __construct(
        private CoverageService $coverage,
        private CardGenerator $cards,
    ) {}

    /**
     * @return array{created:int, remaining:int, done:bool, rounds:int}
     */
    public function fill(int $maxRounds = 6, int $batchSize = 12): array
    {
        $created = 0;
        $dryStreak = 0;
        $rounds = 0;

        for ($i = 0; $i < $maxRounds; $i++) {
            $before = $this->coverage->summary()['covered_slots'];

            $req = $this->coverage->gapRequirements($batchSize);
            if (empty($req['verbUses']) && empty($req['words'])) {
                break; // fully covered
            }

            $rounds++;
            $created += $this->cards->generateForGaps($req['verbUses'], $req['words']);

            $after = $this->coverage->summary()['covered_slots'];

            if ($after <= $before) {
                // No new coverage this round; give it one more try, then stop.
                if (++$dryStreak >= 2) {
                    break;
                }
            } else {
                $dryStreak = 0;
            }
        }

        $remaining = $this->coverage->summary()['gap_count'];

        return [
            'created' => $created,
            'remaining' => $remaining,
            'done' => $remaining === 0,
            'rounds' => $rounds,
        ];
    }
}
