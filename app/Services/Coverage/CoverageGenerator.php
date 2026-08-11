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
     * New cards land as drafts, so progress is measured on "addressed" slots
     * (approved + draft): done means every gap has at least a draft awaiting
     * review, not that the displayed coverage percent hit 100.
     *
     * @return array{created:int, remaining:int, awaiting_review:int, done:bool, rounds:int}
     */
    public function fill(int $maxRounds = 6, int $batchSize = 12): array
    {
        $created = 0;
        $dryStreak = 0;
        $rounds = 0;

        $addressed = fn (array $s): int => $s['covered_slots'] + $s['draft_slots'];

        for ($i = 0; $i < $maxRounds; $i++) {
            $before = $addressed($this->coverage->summary());

            $req = $this->coverage->gapRequirements($batchSize);
            if (empty($req['verbUses']) && empty($req['words'])) {
                break; // every slot has an approved or draft card
            }

            $rounds++;
            $created += $this->cards->generateForGaps($req['verbUses'], $req['words']);

            $after = $addressed($this->coverage->summary());

            if ($after <= $before) {
                // No new slots addressed this round; give it one more try, then stop.
                if (++$dryStreak >= 2) {
                    break;
                }
            } else {
                $dryStreak = 0;
            }
        }

        $summary = $this->coverage->summary();

        return [
            'created' => $created,
            'remaining' => $summary['open_gap_count'],
            'awaiting_review' => $summary['draft_slots'],
            'done' => $summary['open_gap_count'] === 0,
            'rounds' => $rounds,
        ];
    }
}
