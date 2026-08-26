<?php

use App\Livewire\Admin\Cards;
use App\Livewire\Admin\Coverage;
use App\Livewire\Admin\Generation;
use App\Livewire\Admin\Lessons;
use App\Livewire\Admin\LessonView;
use App\Livewire\Admin\MyPractice;
use App\Livewire\Admin\MyVerbs;
use App\Livewire\Admin\Patterns;
use App\Livewire\Admin\Progress;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\VerbsGrid;
use App\Livewire\Admin\WordsLibrary;
use App\Livewire\Dashboard;
use App\Livewire\Landing;
use App\Models\Card;
use App\Models\Lesson;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Landing::class)->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', Dashboard::class)->name('dashboard');

    Route::livewire('admin/verbs', VerbsGrid::class)->name('admin.verbs');
    Route::livewire('admin/words', WordsLibrary::class)->name('admin.words');
    Route::livewire('admin/patterns', Patterns::class)->name('admin.patterns');
    Route::livewire('admin/cards', Cards::class)->name('admin.cards');
    Route::livewire('admin/generate', Generation::class)->name('admin.generate');
    Route::livewire('admin/coverage', Coverage::class)->name('admin.coverage');
    Route::livewire('admin/progress', Progress::class)->name('admin.progress');
    Route::livewire('admin/lessons', Lessons::class)->name('admin.lessons');
    Route::livewire('admin/lessons/{lesson}', LessonView::class)->name('admin.lessons.show');
    Route::get('admin/lessons/{lesson}/print', fn (Lesson $lesson) => view('print-lesson', ['lesson' => $lesson]))
        ->name('admin.lessons.print');

    Route::livewire('admin/my-verbs', MyVerbs::class)->name('admin.my-verbs');
    Route::livewire('admin/practice', MyPractice::class)->name('admin.practice');
    Route::livewire('admin/settings', Settings::class)->name('admin.settings');

    Route::get('admin/print-cards', function () {
        $type = in_array(request('type'), ['words', 'verbs'], true) ? request('type') : 'all';

        $cards = Card::active()
            ->where('source', 'vocab')
            ->get()
            ->filter(fn (Card $c) => match ($type) {
                'words' => ($c->uses_concepts[0]['type'] ?? null) === 'word',
                'verbs' => ($c->uses_concepts[0]['type'] ?? null) === 'verb',
                default => true,
            })
            ->sortBy('spanish', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('print-cards', ['cards' => $cards, 'type' => $type]);
    })->name('admin.print-cards');
});

require __DIR__.'/settings.php';

require __DIR__.'/kids.php';
