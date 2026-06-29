<?php

namespace App\Livewire\Admin;

use App\Models\Pattern;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Patterns')]
class Patterns extends Component
{
    public ?int $editingId = null;

    #[Validate('required|string|max:80')]
    public string $name = '';

    #[Validate('required|string|max:500')]
    public string $instruction = '';

    public function openAdd(): void
    {
        $this->reset(['editingId', 'name', 'instruction']);
        $this->resetValidation();
        Flux::modal('pattern-form')->show();
    }

    public function openEdit(int $id): void
    {
        $pattern = Pattern::findOrFail($id);
        $this->editingId = $pattern->id;
        $this->name = $pattern->name;
        $this->instruction = $pattern->instruction;
        $this->resetValidation();
        Flux::modal('pattern-form')->show();
    }

    public function save(): void
    {
        $this->validate();

        Pattern::updateOrCreate(
            ['id' => $this->editingId],
            ['name' => trim($this->name), 'instruction' => trim($this->instruction)],
        );

        Flux::modal('pattern-form')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? 'Pattern updated.' : 'Pattern added.');
    }

    public function toggle(int $id): void
    {
        $pattern = Pattern::findOrFail($id);
        $pattern->enabled = ! $pattern->enabled;
        $pattern->save();
    }

    public function delete(int $id): void
    {
        Pattern::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: 'Pattern removed.');
    }

    public function render()
    {
        return view('livewire.admin.patterns', [
            'patterns' => Pattern::orderBy('name')->get(),
        ]);
    }
}
