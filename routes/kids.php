<?php

use App\Livewire\Kid\Login;
use App\Livewire\Kid\Review;
use Illuminate\Support\Facades\Route;

Route::livewire('start', Login::class)->name('kid.login');

Route::livewire('play', Review::class)
    ->middleware('auth:kid')
    ->name('kid.review');
