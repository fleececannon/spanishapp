<?php

use App\Livewire\Kid\Dashboard;
use App\Livewire\Kid\Login;
use App\Livewire\Kid\Review;
use Illuminate\Support\Facades\Route;

Route::livewire('start', Login::class)->name('kid.login');

Route::middleware('auth:kid')->group(function () {
    Route::livewire('home', Dashboard::class)->name('kid.home');

    Route::livewire('play', Review::class)->name('kid.review');
});
