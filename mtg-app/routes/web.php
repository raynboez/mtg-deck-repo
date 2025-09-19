<?php

use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DeckController;

Route::get('/', function () {
    return Inertia::render('Import');
})->middleware(['auth', 'verified'])->name('home');

Route::get('deck_import', function () {
    return Inertia::render('Import');
})->middleware(['auth', 'verified'])->name('deck_import');

Route::get('stats', function () {
    return Inertia::render('Stats');
})->middleware(['auth', 'verified'])->name('stats');

Route::get('match_import', function () {
    return Inertia::render('ImportMatch');
})->middleware(['auth', 'verified'])->name('match_import');

Route::get('deck/{id}', [DeckController::class, 'show']
)->middleware(['auth', 'verified'])->name('deck');


Route::get('bans', function () {
    return Inertia::render('Bans');
})->middleware(['auth', 'verified'])->name('bans');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
