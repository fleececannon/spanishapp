<?php

use App\Livewire\Admin\Cards;
use App\Livewire\Admin\Generation;
use App\Livewire\Admin\Patterns;
use App\Livewire\Admin\Progress;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\VerbsGrid;
use App\Livewire\Admin\WordsLibrary;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('admin/verbs', VerbsGrid::class)->name('admin.verbs');
    Route::livewire('admin/words', WordsLibrary::class)->name('admin.words');
    Route::livewire('admin/patterns', Patterns::class)->name('admin.patterns');
    Route::livewire('admin/cards', Cards::class)->name('admin.cards');
    Route::livewire('admin/generate', Generation::class)->name('admin.generate');
    Route::livewire('admin/progress', Progress::class)->name('admin.progress');
    Route::livewire('admin/settings', Settings::class)->name('admin.settings');
});

require __DIR__.'/settings.php';

require __DIR__.'/kids.php';
