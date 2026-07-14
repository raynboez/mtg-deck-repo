<?php

use App\Http\Controllers\StatsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DeckController;
use App\Http\Controllers\ArmyController;

Route::get('/', function () {
    return Inertia::render('Stats');
})->middleware(['auth', 'verified'])->name('home');

Route::get('deck_import', function () {
    return Inertia::render('Import');
})->middleware(['auth', 'verified'])->name('deck_import');

Route::get('warhammer/army_import', function () {
    return Inertia::render('WarhammerImport');
})->middleware(['auth', 'verified'])->name('WarhammerArmyImport');

Route::get('stats', function () {
    return Inertia::render('Stats');
})->middleware(['auth', 'verified'])->name('stats');

Route::get('warhammer/stats', function () {
    return Inertia::render('WarhammerStats');
})->middleware(['auth', 'verified'])->name('WarhammerStats');


Route::get('match_import', function () {
    return Inertia::render('ImportMatch');
})->middleware(['auth', 'verified'])->name('match_import');

Route::get('warhammer/match_import', function () {
    return Inertia::render('WarhammerImportMatch');
})->middleware(['auth', 'verified'])->name('WarhammerImportMatch');

Route::get('warhammer/army/{id}', [ArmyController::class, 'show']
)->middleware(['auth', 'verified'])->name('army');


Route::get('deck/{id}', [DeckController::class, 'show']
)->middleware(['auth', 'verified'])->name('deck');


Route::get('bans', function () {
    return Inertia::render('Bans');
})->middleware(['auth', 'verified'])->name('bans');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
