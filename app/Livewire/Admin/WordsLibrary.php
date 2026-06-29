<?php

namespace App\Livewire\Admin;

use App\Enums\WordCategory;
use App\Enums\WordRole;
use App\Models\Word;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Words')]
class WordsLibrary extends Component
{
    public ?int $editingId = null;

    #[Validate('required|string|max:60')]
    public string $wSpanish = '';

    #[Validate('required|string|max:120')]
    public string $wEnglish = '';

    #[Validate('required')]
    public string $wCategory = 'connector';

    #[Validate('nullable|string|max:1')]
    public ?string $wGender = null;

    #[Validate('required')]
    public string $wRole = 'target';

    public function openAdd(): void
    {
        $this->reset(['editingId', 'wSpanish', 'wEnglish', 'wGender']);
        $this->wCategory = 'connector';
        $this->wRole = 'target';
        $this->resetValidation();
        Flux::modal('word-form')->show();
    }

    public function openEdit(int $id): void
    {
        $word = Word::findOrFail($id);
        $this->editingId = $word->id;
        $this->wSpanish = $word->spanish;
        $this->wEnglish = $word->english;
        $this->wCategory = $word->category->value;
        $this->wGender = $word->gender;
        $this->wRole = $word->role->value;
        $this->resetValidation();
        Flux::modal('word-form')->show();
    }

    public function save(): void
    {
        $this->validate();

        Word::updateOrCreate(
            ['id' => $this->editingId],
            [
                'spanish' => trim($this->wSpanish),
                'english' => trim($this->wEnglish),
                'category' => WordCategory::from($this->wCategory),
                'gender' => $this->wGender ?: null,
                'role' => WordRole::from($this->wRole),
            ],
        );

        Flux::modal('word-form')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? 'Word updated.' : 'Word added.');
    }

    public function toggleUnlocked(int $id): void
    {
        $word = Word::findOrFail($id);
        $word->unlocked = ! $word->unlocked;
        $word->save();
    }

    public function toggleRole(int $id): void
    {
        $word = Word::findOrFail($id);
        $word->role = $word->role === WordRole::Target ? WordRole::Ingredient : WordRole::Target;
        $word->save();
    }

    public function delete(int $id): void
    {
        Word::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: 'Word removed.');
    }

    public function render()
    {
        return view('livewire.admin.words-library', [
            'wordsByCategory' => Word::orderBy('category')->orderBy('spanish')->get()->groupBy(fn ($w) => $w->category->value),
            'ingredientNouns' => Word::ingredientNounCount(),
            'unlockedCount' => Word::unlocked()->count(),
            'totalCount' => Word::count(),
        ]);
    }
}
