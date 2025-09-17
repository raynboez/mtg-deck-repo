<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeckImportController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\DeckController;

use App\Http\Controllers\SeasonController;
use App\Http\Controllers\BanController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['middleware' => ['web']], function()
{

    Route::post('/decks/import', [DeckImportController::class, 'import']);
    Route::put('/decks/{deck}', [DeckImportController::class, 'update']);
    Route::put('/decks/{deck}/add', [DeckImportController::class, 'addCard']);
    Route::post('/decks/{deck}/remove', [DeckImportController::class, 'removeCard']);

    Route::get('/decks/user', [DeckController::class, 'userDecks']);    
    Route::get('/decks/user/{userId}', [DeckController::class, 'userDecksById']);
    Route::get('/decks/{userId}', [DeckController::class, 'getDeck']);
    Route::get('/users', [DeckController::class, 'getUsers']);
    Route::get('user/current', function (){return auth()->user()->user_id;});
    Route::get('/getDeck/{deckId}', [DeckController::class, 'getDeckExport']);

    Route::put('/matchRecord', [MatchController::class, 'store']);
    Route::get('/stats', [StatsController::class, 'index']);
    Route::get('/stats/player/{playerId}', [StatsController::class, 'playerStats']);

        
    Route::get('/seasons', [SeasonController::class, 'index']);
    Route::get('/seasons/active', [SeasonController::class, 'getActiveSeasons']);


    Route::prefix('banlist')->group(function () {
        Route::get('/season/{seasonId}', [BanController::class, 'getBannedCardList']);
        Route::post('/add', [BanController::class, 'addBannedCard']);
    });
});
