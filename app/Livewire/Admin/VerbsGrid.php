<?php

namespace App\Livewire\Admin;

use App\Enums\Tense;
use App\Enums\VerbClass;
use App\Models\Verb;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Title('Verbs')]
class VerbsGrid extends Component
{
    #[Validate('required|string|max:60')]
    public string $newSpanish = '';

    #[Validate('required|string|max:120')]
    public string $newEnglish = '';

    #[Validate('required|string|max:60')]
    public string $newTag = 'Key Verbs';

    #[Validate('required')]
    public string $newClass = 'AR';

    /** The tense columns shown in the grid. */
    public function tenses(): array
    {
        return Tense::cases();
    }

    public function toggleTense(int $verbId, string $tense): void
    {
        $verb = Verb::findOrFail($verbId);
        $tenses = $verb->enabled_tenses ?? [];

        $verb->enabled_tenses = in_array($tense, $tenses, true)
            ? array_values(array_diff($tenses, [$tense]))
            : [...$tenses, $tense];

        $verb->save();
    }

    public function toggleUnlocked(int $verbId): void
    {
        $verb = Verb::findOrFail($verbId);
        $verb->unlocked = ! $verb->unlocked;
        $verb->save();
    }

    public function toggleDrill(int $verbId): void
    {
        $verb = Verb::findOrFail($verbId);
        $verb->drill_all_forms = ! $verb->drill_all_forms;
        $verb->save();
    }

    public function addVerb(): void
    {
        $this->validate();

        Verb::create([
            'spanish' => trim($this->newSpanish),
            'english' => trim($this->newEnglish),
            'tag' => trim($this->newTag),
            'verb_class' => VerbClass::from($this->newClass),
            'enabled_tenses' => ['infinitive'],
            'drill_all_forms' => false,
            'unlocked' => false,
        ]);

        $this->reset(['newSpanish', 'newEnglish']);
        Flux::modal('add-verb')->close();
        Flux::toast(variant: 'success', text: 'Verb added.');
    }

    public function render()
    {
        $verbs = Verb::orderBy('tag')->orderBy('spanish')->get()->groupBy('tag');

        return view('livewire.admin.verbs-grid', [
            'verbsByTag' => $verbs,
            'tenses' => $this->tenses(),
            'unlockedCount' => Verb::unlocked()->count(),
            'totalCount' => Verb::count(),
        ]);
    }
}
