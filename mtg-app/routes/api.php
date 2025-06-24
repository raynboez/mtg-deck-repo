<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeckImportController;
use App\Http\Controllers\DeckController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['middleware' => ['web']], function()
{

    Route::post('/decks/import', [DeckImportController::class, 'import']);
    Route::put('/decks/{deck}', [DeckImportController::class, 'update']);

    Route::get('/decks/user', [DeckController::class, 'userDecks']);    
    Route::get('/decks/user/{userId}', [DeckController::class, 'userDecksById']);
    Route::get('/decks/{userId}', [DeckController::class, 'getDeck']);
    Route::get('/users', [DeckController::class, 'getUsers']);
    Route::get('/getDeck/{deckId}', [DeckController::class, 'getDeckJson']);
});
