<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DeckController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('deck_import', function () {
    return Inertia::render('Import');
})->middleware(['auth', 'verified'])->name('deck_import');

Route::get('deck/{id}', [DeckController::class, 'show']
)->middleware(['auth', 'verified'])->name('deck');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
