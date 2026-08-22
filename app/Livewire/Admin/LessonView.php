<?php

namespace App\Livewire\Admin;

use App\Models\Lesson;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Lesson')]
class LessonView extends Component
{
    public Lesson $lesson;

    public function render()
    {
        return view('livewire.admin.lesson-view');
    }
}
