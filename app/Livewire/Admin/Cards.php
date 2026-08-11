<?php

namespace App\Livewire\Admin;

use App\Enums\CardSource;
use App\Enums\CardStatus;
use App\Models\Card;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Cards')]
class Cards extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'active';

    public ?int $editingId = null;

    #[Validate('required|string|max:300')]
    public string $cSpanish = '';

    #[Validate('required|string|max:300')]
    public string $cEnglish = '';

    #[Validate('nullable|string|max:40')]
    public ?string $mmTense = null;

    #[Validate('nullable|string|max:40')]
    public ?string $mmSubject = null;

    #[Validate('nullable|string|max:10')]
    public ?string $mmGender = null;

    public string $bulkText = '';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function openBulk(): void
    {
        $this->reset(['bulkText']);
        $this->resetValidation();
        Flux::modal('card-bulk')->show();
    }

    /** Paste many cards at once: one per line, "Spanish <tab or |> English". */
    public function importBulk(): void
    {
        $created = 0;
        $skipped = 0;

        foreach (preg_split('/\r\n|\r|\n/', trim($this->bulkText)) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $delim = str_contains($line, "\t") ? "\t" : (str_contains($line, '|') ? '|' : null);
            if ($delim === null) {
                $skipped++;

                continue;
            }

            [$spanish, $english] = array_pad(explode($delim, $line, 2), 2, '');
            $spanish = trim($spanish);
            $english = trim($english);

            if ($spanish === '' || $english === '') {
                $skipped++;

                continue;
            }

            Card::create([
                'source' => CardSource::Manual,
                'spanish' => mb_substr($spanish, 0, 300),
                'english' => mb_substr($english, 0, 300),
                'test_direction' => 'es_to_en',
                'uses_concepts' => [],
                'must_match' => ['tense' => null, 'subject' => null, 'gender' => null],
                'status' => CardStatus::Active,
            ]);
            $created++;
        }

        if ($created === 0) {
            $this->addError('bulkText', 'No valid rows found. Put Spanish then English on each line, separated by a Tab or a |.');

            return;
        }

        Flux::modal('card-bulk')->close();
        Flux::toast(variant: 'success', text: "{$created} card(s) added.".($skipped ? " {$skipped} line(s) skipped." : ''));
        $this->filter = 'active';
        $this->resetPage();
    }

    public function openAdd(): void
    {
        $this->reset(['editingId', 'cSpanish', 'cEnglish', 'mmTense', 'mmSubject', 'mmGender']);
        $this->resetValidation();
        Flux::modal('card-form')->show();
    }

    public function openEdit(int $id): void
    {
        $card = Card::findOrFail($id);
        $this->editingId = $card->id;
        $this->cSpanish = $card->spanish;
        $this->cEnglish = $card->english;
        $this->mmTense = $card->must_match['tense'] ?? null;
        $this->mmSubject = $card->must_match['subject'] ?? null;
        $this->mmGender = $card->must_match['gender'] ?? null;
        $this->resetValidation();
        Flux::modal('card-form')->show();
    }

    public function save(): void
    {
        $this->validate();

        $mustMatch = [
            'tense' => $this->mmTense ?: null,
            'subject' => $this->mmSubject ?: null,
            'gender' => $this->mmGender ?: null,
        ];

        if ($this->editingId) {
            $card = Card::findOrFail($this->editingId);
            $card->update([
                'spanish' => trim($this->cSpanish),
                'english' => trim($this->cEnglish),
                'must_match' => $mustMatch,
            ]);
        } else {
            Card::create([
                'source' => CardSource::Manual,
                'spanish' => trim($this->cSpanish),
                'english' => trim($this->cEnglish),
                'test_direction' => 'es_to_en',
                'uses_concepts' => [],
                'must_match' => $mustMatch,
                'status' => CardStatus::Active,
            ]);
        }

        Flux::modal('card-form')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? 'Card updated.' : 'Card added.');
    }

    public function approve(int $id): void
    {
        Card::whereKey($id)->update(['status' => CardStatus::Active]);
    }

    public function approveAllDrafts(): void
    {
        $count = Card::draft()->update(['status' => CardStatus::Active]);
        Flux::toast(variant: 'success', text: "Approved {$count} draft card(s).");
        $this->resetPage();
    }

    public function retire(int $id): void
    {
        Card::whereKey($id)->update(['status' => CardStatus::Retired]);
    }

    public function activate(int $id): void
    {
        Card::whereKey($id)->update(['status' => CardStatus::Active]);
    }

    public function delete(int $id): void
    {
        Card::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: 'Card deleted.');
    }

    public function render()
    {
        $query = Card::query()->latest();

        if ($this->filter === 'draft') {
            $query->where('status', CardStatus::Draft->value);
        } elseif ($this->filter === 'active') {
            $query->where('status', CardStatus::Active->value);
        } elseif ($this->filter === 'retired') {
            $query->where('status', CardStatus::Retired->value);
        }

        return view('livewire.admin.cards', [
            'cards' => $query->paginate(20),
            'draftCount' => Card::draft()->count(),
        ]);
    }
}
