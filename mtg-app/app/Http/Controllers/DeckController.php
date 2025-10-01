<?php

namespace App\Http\Controllers;

use DivisionByZeroError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\DeckExportService;
use App\Http\Controllers\SeasonController;
use App\Models\Deck;
use Inertia\Inertia;

class DeckController extends Controller
{        
    protected $seasonController;

    public function __construct(SeasonController $seasonController)
    {
        $this->seasonController = $seasonController;
    }
    public function userDecks(Request $request)
    {
        $user = $request->user()->user_id;
        $decks = DB::table('decks')->where('user_id', $user)->get();
        return response()->json($decks);
    }

    public function getUsers(Request $request)
    {
        $users = DB::table('users')->select('user_id', 'name')->get();
        return response()->json($users);
    }

    public function getDeck(Request $request, $deckId)
    {
        $user = $request->user()->user_id;
        $deckInfo = DB::table('decks')->where('deck_id', $deckId)->first();
        $deck = DB::table('deck_cards')->where('deck_id', $deckId)->where('is_main_deck', 1)->get();
        $banned = DB::table('banned_cards')
            ->join('cards', 'cards.card_id', '=', 'banned_cards.card_id')
            ->where('banned_cards.season_id', $this->seasonController->getActiveSeasonId())
            ->pluck('cards.oracle_id')
            ->toArray();
        $commanders = DB::table('deck_cards')->where([['deck_id', $deckId],['is_commander', true]])->select('card_id')->get();
        $commanderArr = array();
        $potentialCommanderArr = array();
        foreach($commanders as $commander){
            array_push($commanderArr, $commander->card_id);
        }
        $cardCount = 0;
        $containsBannedCards = 0;
        $cardArr = array();
        $reverse = array();

        foreach($deck as $card){
            $cardData = DB::table('cards')->where('card_id', $card->card_id)->first();
            $cardData->quantity = $card->quantity;
            
            if (in_array($cardData->oracle_id, $banned)) {
                $cardData->is_banned = 1;
                $containsBannedCards+= 1;
            } else {
                $cardData->is_banned = 0;
            }
            if(str_contains($cardData->type_line,'Legendary')){
                array_push($potentialCommanderArr, $cardData);
            }
            if($cardData->reverse_card_id)
            {   
                $reverseCardData = DB::table('reverse_cards')->where('face_card_id', $card->card_id)->first();
                array_push($reverse, $reverseCardData);
            }
            array_push($cardArr, $cardData);
            $cardCount+=$cardData->quantity;
        }

                usort($cardArr, function($a, $b) {
            return strcmp($a->card_name, $b->card_name);
        });

        usort($potentialCommanderArr, function($a, $b) {
            return strcmp($a->card_name, $b->card_name);
        });


        $personalwins = count(DB::table('match_participants')->where('user_id', $user)->where('deck_id', $deckId)->where('is_winner', 1)->get());
        $personalgames = count(DB::table('match_participants')->where( 'user_id', $user)->where('deck_id', $deckId)->get());
        $personalloss = $personalgames - $personalwins;
        try{
        $personalpercent = number_format(($personalwins / $personalgames) * 100); 
        }
        catch(DivisionByZeroError){
            $personalpercent = 0;
        }
        $wins = count(DB::table('match_participants')->where('deck_id', $deckId)->where('is_winner', 1)->get());
        $games = count(DB::table('match_participants')->where('deck_id', $deckId)->get());
        $loss = $games - $wins;
        try{
        $percent = number_format(($wins / $games) * 100); 
        }
        catch(DivisionByZeroError){
            $percent = 0;
        }

        if($personalgames != $games){
            $deckstats = $wins . "-" . $loss . " (" . $percent . "% of $games).\nPersonal Win-Loss: " . $personalwins . "-" . $personalloss . " (" . $personalpercent . "% of $personalgames)";
        } else {
            $deckstats = $wins . "-" . $loss . " (" . $percent . "% of $games)";
        }
        
        return response()->json(
            [
                'deck' => $deckInfo,
                'cards' => $cardArr,
                'reverse' => $reverse,
                'commanders' => $commanderArr,
                'potentialCommanders' => $potentialCommanderArr,
                'deckstats' => $deckstats,
                'cardcount' => $cardCount,
                'containsBannedCards' => $containsBannedCards,
                'url' => $deckInfo->url,
                'from_url' => $deckInfo->url ? 1 : 0
            ]
        );
    }

    public function show($id)
    {
        $deck = Deck::findOrFail($id);
        $id = $deck->deck_id;
        return Inertia::render('Deck', [
            'deck_id' => $id
        ]);
    }

    public function userDecksById(Request $request, $userId)
    {
        $decks = DB::table('decks')->where('user_id', $userId)->get();
        return response()->json($decks);
    }


    public function getDeckJson(Request $request, $deckId)
    {
        $deck = DB::table('deck_cards')->where('deck_id', $deckId)->get();
        $cardArr = array();
        foreach($deck as $card){
            $cardData = DB::table('cards')->where('card_id', $card->card_id)->first();
            $cardData->quantity = $card->quantity;
            
            array_push($cardArr, $cardData);
        }

        return response()->json($cardArr);
    }

    public function getDeckExport(Request $request, $deck_id)
    {
        $deck = Deck::findOrFail($deck_id);
        $exportText = app(DeckExportService::class)->generateExportText($deck);      
        
        return response()->make($exportText, 200, ['Content-Type' => 'text/plain']);
    }

    public function getOverrides(Request $request, $deckId)
    {
        $seasonId = $this->seasonController->getActiveSeasonId();
        $overrides = DB::table('overridden_cards')
            ->where('deck_id', $deckId)
            ->where('season_id', $seasonId)
            ->get();

        $result = [];
        foreach ($overrides as $override) {
            $baseCard = DB::table('cards')->where('card_id', $override->base_card_id)->first();
            $overrideCard = DB::table('cards')->where('card_id', $override->override_card_id)->first();
            if ($baseCard && $overrideCard) {
                $result[] = [
                    'base_card' => $baseCard,
                    'override_card' => $overrideCard
                ];
            }
        }

        return response()->json($result);
    }
}
