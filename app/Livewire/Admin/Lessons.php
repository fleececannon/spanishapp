<?php

namespace App\Livewire\Admin;

use App\Models\Lesson;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Lessons')]
class Lessons extends Component
{
    public ?int $editingId = null;

    public int $position = 1;

    public string $title = '';

    public string $subtitle = '';

    public ?int $minutes = null;

    public string $body = '';

    protected function rules(): array
    {
        return [
            'position' => ['required', 'integer', 'min:1', Rule::unique('lessons', 'position')->ignore($this->editingId)],
            'title' => 'required|string|max:120',
            'subtitle' => 'nullable|string|max:255',
            'minutes' => 'nullable|integer|min:1|max:240',
            'body' => 'required|string|max:100000',
        ];
    }

    public function openAdd(): void
    {
        $this->reset(['editingId', 'title', 'subtitle', 'minutes', 'body']);
        $this->position = (int) Lesson::max('position') + 1;
        $this->resetValidation();
        Flux::modal('lesson-form')->show();
    }

    public function openEdit(int $id): void
    {
        $lesson = Lesson::findOrFail($id);
        $this->editingId = $lesson->id;
        $this->position = $lesson->position;
        $this->title = $lesson->title;
        $this->subtitle = $lesson->subtitle ?? '';
        $this->minutes = $lesson->minutes;
        $this->body = $lesson->body;
        $this->resetValidation();
        Flux::modal('lesson-form')->show();
    }

    public function save(): void
    {
        $this->validate();

        Lesson::updateOrCreate(
            ['id' => $this->editingId],
            [
                'position' => $this->position,
                'title' => trim($this->title),
                'subtitle' => trim($this->subtitle) ?: null,
                'minutes' => $this->minutes,
                'body' => $this->body,
            ],
        );

        Flux::modal('lesson-form')->close();
        Flux::toast(variant: 'success', text: $this->editingId ? 'Lesson updated.' : 'Lesson added.');
    }

    public function delete(int $id): void
    {
        Lesson::findOrFail($id)->delete();
        Flux::toast(variant: 'success', text: 'Lesson removed.');
    }

    public function render()
    {
        return view('livewire.admin.lessons', [
            'lessons' => Lesson::orderBy('position')->get(),
        ]);
    }
}
