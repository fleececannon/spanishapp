<?php

namespace App\Livewire\Admin;

use App\Models\Card;
use App\Models\Verb;
use App\Services\Claude\CardGenerator;
use App\Services\Claude\ClaudeException;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Generate cards')]
class Generation extends Component
{
    #[Validate('required|integer|min:1|max:20')]
    public int $count = 5;

    #[Validate('nullable|string|max:200')]
    public ?string $emphasis = null;

    public function generate(CardGenerator $generator): void
    {
        $this->validate();

        try {
            $created = $generator->generate($this->count, $this->emphasis ?: null);
        } catch (ClaudeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        Flux::toast(
            variant: $created > 0 ? 'success' : 'warning',
            text: $created > 0
                ? "Added {$created} card".($created === 1 ? '' : 's').'.'
                : 'No cards passed the fence — try again or adjust the unlocked set.',
        );
    }

    public function render()
    {
        return view('livewire.admin.generation', [
            'recent' => Card::active()->latest()->limit(15)->get(),
            'activeCount' => Card::active()->count(),
            'unlockedVerbs' => Verb::unlocked()->count(),
        ]);
    }
}
