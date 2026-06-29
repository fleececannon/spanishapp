<?php

use App\Livewire\Admin\Generation;
use App\Livewire\Admin\VerbsGrid;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('admin/verbs', VerbsGrid::class)->name('admin.verbs');
    Route::livewire('admin/generate', Generation::class)->name('admin.generate');
});

require __DIR__.'/settings.php';

require __DIR__.'/kids.php';
