<?php

namespace App\Livewire\Admin;

use App\Services\Claude\ClaudeException;
use App\Services\Coverage\CoverageGenerator;
use App\Services\Coverage\CoverageService;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Coverage')]
class Coverage extends Component
{
    /** Whether the auto-stepping fill loop is active. */
    public bool $running = false;

    /** Rounds in a row that produced no new coverage (safety to stop). */
    public int $idleSteps = 0;

    public int $createdThisRun = 0;

    public function start(): void
    {
        $this->running = true;
        $this->idleSteps = 0;
        $this->createdThisRun = 0;
    }

    public function stop(): void
    {
        $this->running = false;
    }

    /**
     * One short round per call (a single Claude request), driven by wire:poll
     * so the dev server is never tied up in a long multi-call request.
     */
    public function step(CoverageGenerator $generator): void
    {
        if (! $this->running) {
            return;
        }

        try {
            $result = $generator->fill(maxRounds: 1, batchSize: 12);
        } catch (ClaudeException $e) {
            $this->running = false;
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->createdThisRun += $result['created'];

        if ($result['done']) {
            $this->running = false;
            Flux::toast(
                variant: 'success',
                text: $result['awaiting_review'] > 0
                    ? "All gaps have cards — {$this->createdThisRun} new draft(s) this run. Approve them on the Cards page to count them as covered."
                    : "Full coverage! Added {$this->createdThisRun} card(s) this run.",
            );

            return;
        }

        if ($result['created'] === 0) {
            if (++$this->idleSteps >= 2) {
                $this->running = false;
                Flux::toast(variant: 'warning', text: "Couldn't cover the remaining {$result['remaining']} slot(s). Try adjusting the unlocked set.");
            }

            return;
        }

        $this->idleSteps = 0;
    }

    public function render(CoverageService $coverage)
    {
        return view('livewire.admin.coverage', [
            'summary' => $coverage->summary(),
        ]);
    }
}
