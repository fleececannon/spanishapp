<?php

namespace App\Livewire\Admin;

use App\Models\MyVerb;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('My Verbs')]
class MyVerbs extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'all'; // all | training | known | waiting

    public string $sort = 'score'; // score | spanish

    // Add-verb modal.
    public string $newSpanish = '';

    public string $newEnglish = '';

    public int $newScore = 50;

    // Bulk paste modal.
    public string $bulk = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $sort): void
    {
        $this->sort = in_array($sort, ['score', 'spanish'], true) ? $sort : 'score';
        $this->resetPage();
    }

    public function toggleUnlocked(int $id): void
    {
        $verb = MyVerb::findOrFail($id);
        $verb->unlocked = ! $verb->unlocked;
        $verb->save();
    }

    public function toggleMastered(int $id): void
    {
        $verb = MyVerb::findOrFail($id);
        $verb->mastered = ! $verb->mastered;
        $verb->save();
    }

    public function openAdd(): void
    {
        $this->reset(['newSpanish', 'newEnglish', 'newScore']);
        $this->resetValidation();
        Flux::modal('my-verb-form')->show();
    }

    public function addVerb(): void
    {
        $this->validate([
            'newSpanish' => 'required|string|max:60|unique:my_verbs,spanish',
            'newEnglish' => 'required|string|max:255',
            'newScore' => 'required|integer|min:1|max:100',
        ]);

        MyVerb::create([
            'spanish' => mb_strtolower(trim($this->newSpanish)),
            'english' => trim($this->newEnglish),
            'frequency_score' => $this->newScore,
        ]);

        Flux::modal('my-verb-form')->close();
        Flux::toast(variant: 'success', text: 'Verb added.');
    }

    public function openBulk(): void
    {
        $this->reset('bulk');
        Flux::modal('my-verb-bulk')->show();
    }

    /** One verb per line: "spanish | english" or "spanish | english | score" (tabs work too). */
    public function importBulk(): void
    {
        $added = 0;
        $skipped = 0;

        foreach (preg_split('/\R/u', $this->bulk) as $line) {
            $parts = array_map('trim', preg_split('/\t|\|/', $line));
            if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
                if (trim($line) !== '') {
                    $skipped++;
                }

                continue;
            }

            $spanish = mb_strtolower($parts[0]);
            if (MyVerb::where('spanish', $spanish)->exists()) {
                $skipped++;

                continue;
            }

            MyVerb::create([
                'spanish' => $spanish,
                'english' => $parts[1],
                'frequency_score' => min(100, max(1, (int) ($parts[2] ?? 50) ?: 50)),
            ]);
            $added++;
        }

        Flux::modal('my-verb-bulk')->close();
        Flux::toast(variant: 'success', text: "{$added} added".($skipped ? ", {$skipped} skipped" : '.'));
    }

    public function delete(int $id): void
    {
        MyVerb::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: 'Verb removed.');
    }

    public function render()
    {
        $query = MyVerb::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('spanish', 'like', '%'.trim($this->search).'%')
                ->orWhere('english', 'like', '%'.trim($this->search).'%')))
            ->when($this->filter === 'training', fn ($q) => $q->inTraining())
            ->when($this->filter === 'known', fn ($q) => $q->where('mastered', true))
            ->when($this->filter === 'waiting', fn ($q) => $q->where('unlocked', false)->where('mastered', false));

        $query = $this->sort === 'spanish'
            ? $query->orderBy('spanish')
            : $query->orderByDesc('frequency_score')->orderBy('spanish');

        return view('livewire.admin.my-verbs', [
            'verbs' => $query->paginate(50),
            'total' => MyVerb::count(),
            'known' => MyVerb::where('mastered', true)->count(),
            'training' => MyVerb::inTraining()->count(),
            'dueToday' => MyVerb::inTraining()->whereDate('due', '<=', Carbon::today())->count(),
        ]);
    }
}
