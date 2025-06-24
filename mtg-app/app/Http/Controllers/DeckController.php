<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Deck;
use Inertia\Inertia;

class DeckController extends Controller
{        
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
        $deckInfo = DB::table('decks')->where('deck_id', $deckId)->first();
        $deck = DB::table('deck_cards')->where('deck_id', $deckId)->get();
        $commanders = DB::table('deck_cards')->where([['deck_id', $deckId],['is_commander', true]])->select('card_id')->get();
        $cardArr = array();
        $reverse = array();
        foreach($deck as $card){
            $cardData = DB::table('cards')->where('card_id', $card->card_id)->first();
            $cardData->quantity = $card->quantity;
            if($cardData->reverse_card_id)
            {   
                $reverseCardData = DB::table('reverse_cards')->where('face_card_id', $card->card_id)->first();
                array_push($reverse, $reverseCardData);
            }
            array_push($cardArr, $cardData);
        }

        return response()->json(
            [
                'deck' => $deckInfo,
                'cards' => $cardArr,
                'reverse' => $reverse,
                'commanders' => json_encode($commanders)
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


    //TTS api response (no point getting this working)
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
}
