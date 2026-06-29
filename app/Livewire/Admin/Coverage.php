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
    public function fillGaps(CoverageGenerator $generator): void
    {
        try {
            $result = $generator->fill();
        } catch (ClaudeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        if ($result['done']) {
            Flux::toast(variant: 'success', text: "Full coverage! Added {$result['created']} card(s).");
        } elseif ($result['created'] > 0) {
            Flux::toast(variant: 'success', text: "Added {$result['created']} card(s) — {$result['remaining']} slot(s) still uncovered. Click again to continue.");
        } else {
            Flux::toast(variant: 'warning', text: "Couldn't cover the remaining {$result['remaining']} slot(s). Try adjusting the unlocked set.");
        }
    }

    public function render(CoverageService $coverage)
    {
        return view('livewire.admin.coverage', [
            'summary' => $coverage->summary(),
        ]);
    }
}
