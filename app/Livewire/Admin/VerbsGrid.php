<?php

namespace App\Livewire\Admin;

use App\Enums\Tense;
use App\Enums\VerbClass;
use App\Models\Verb;
use App\Services\Vocab\VocabCardService;
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

    /** Selected existing group, or '__new__' to create one. */
    public string $newGroup = '';

    public string $newGroupName = '';

    public bool $newVocab = false;

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

    public function toggleVocab(int $verbId, VocabCardService $vocabCards): void
    {
        $verb = Verb::findOrFail($verbId);
        $verb->vocab_card = ! $verb->vocab_card;
        $verb->save();

        $vocabCards->sync($verb);
    }

    public function addVerb(VocabCardService $vocabCards): void
    {
        $this->validate();
        $this->validate([
            'newGroup' => 'required|string|max:60',
            'newGroupName' => $this->newGroup === '__new__' ? 'required|string|max:60' : 'nullable',
        ], [
            'newGroup.required' => 'Pick a group or create a new one.',
            'newGroupName.required' => 'Give the new group a name.',
        ]);

        $verb = Verb::create([
            'spanish' => trim($this->newSpanish),
            'english' => trim($this->newEnglish),
            'tag' => $this->newGroup === '__new__' ? trim($this->newGroupName) : $this->newGroup,
            'verb_class' => $this->classFromInfinitive($this->newSpanish),
            'enabled_tenses' => ['infinitive'],
            'drill_all_forms' => false,
            'unlocked' => false,
            'vocab_card' => $this->newVocab,
        ]);

        $vocabCards->sync($verb);

        $this->reset(['newSpanish', 'newEnglish', 'newGroupName', 'newVocab']);
        Flux::modal('add-verb')->close();
        Flux::toast(variant: 'success', text: 'Verb added.');
    }

    /** AR / ER / IR from the infinitive ending; anything else is irregular. */
    private function classFromInfinitive(string $spanish): VerbClass
    {
        $s = mb_strtolower(trim($spanish));

        return match (true) {
            str_ends_with($s, 'ar') => VerbClass::AR,
            str_ends_with($s, 'er') => VerbClass::ER,
            str_ends_with($s, 'ir') || str_ends_with($s, 'ír') => VerbClass::IR,
            default => VerbClass::Irregular,
        };
    }

    public function render()
    {
        $verbs = Verb::orderBy('tag')->orderBy('spanish')->get()->groupBy('tag');

        return view('livewire.admin.verbs-grid', [
            'verbsByTag' => $verbs,
            'groups' => Verb::query()->distinct()->orderBy('tag')->pluck('tag'),
            'tenses' => $this->tenses(),
            'unlockedCount' => Verb::unlocked()->count(),
            'totalCount' => Verb::count(),
        ]);
    }
}
