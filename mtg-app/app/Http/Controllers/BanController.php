<?php

namespace App\Http\Controllers;

use App\Models\BannedCard;
use App\Models\Season;
use App\Models\User;
use App\Models\Card;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BanController extends Controller
{
    protected $deckImport;

    public function __construct(DeckImportController $importer)
    {
        $this->deckImport = $importer;
    }
    public function addBannedCard(Request $request)
    {
        $validated = $request->validate([
            'season_id' => 'required',
            'scryfallData' => 'required',
            'banned_by' => 'required',
            'notes' => 'nullable|string|max:500'
        ]);
        $season = Season::findOrFail($validated['season_id']);
        $banner = User::findOrFail($validated['banned_by']);
        $cardData = $validated['scryfallData'];
        $card = $this->deckImport->findCard($cardData);

        if(!$card)
                {
                    response()->json([
                'success' => false,
                'message' => 'Failed to add card to banlist',
                'error' => 'Internal server error'
                    ], 500);            
        
                }
        
        
        $existingBannedCard = BannedCard::where('card_id', $card->card_id)
            ->where('season_id', $season->id)
            ->first();

        if ($existingBannedCard) {
            $success = true;
        } else {
            $success = BannedCard::create([
                'season_id' => $season->id,
                'card_id' => $card->card_id,
                'banned_by' => $banner->user_id,
                'notes' => $validated['notes']
            ]);
            
        }
        if($success){
            return response()->json([
                'success' => true,
                'message' => 'Card on Banlist',
                'data' => [
                    'card' => $card->card_name,
                ]
            ], 201);
        } else{
            {
                    response()->json([
                        'success' => false,
                        'message' => 'Failed to add card to banlist',
                        'error' => 'Internal server error'
                    ], 500);            
                }  
        }
    }


    
    public function getBannedCardList(Request $request, $seasonId)
    {
        $season = Season::findOrFail($seasonId);
        $cards = DB::table('banned_cards')->where('season_id', $season->id)->get();
        $cardArr = array();
        $reverse = array();
        foreach($cards as $card){
            $cardData = DB::table('cards')->where('card_id', $card->card_id)->first();
            if($cardData->reverse_card_id)
            {   
                $reverseCardData = DB::table('reverse_cards')->where('face_card_id', $card->card_id)->first();
                array_push($reverse, $reverseCardData);
            }
            
            $cardData->banned_by = User::findOrFail($card->banned_by)->first();
            $cardData->notes = $card->notes;
        
            array_push($cardArr, $cardData);
        }

        return response()->json(
            [
                'cards' => $cardArr,
                'reverse' => $reverse
            ]
        );
    }

    
    public function getBannedCardListAllSeasons(Request $request)
    {
        $cards = DB::table('banned_cards')->get();
        $cardArr = array();
        $reverse = array();
        foreach($cards as $card){
            $cardData = DB::table('cards')->where('card_id', $card->card_id)->first();
            if($cardData->reverse_card_id)
            {   
                $reverseCardData = DB::table('reverse_cards')->where('face_card_id', $card->card_id)->first();
                array_push($reverse, $reverseCardData);
            }
            
            $cardData->banned_by = User::findOrFail($card->banned_by)->first();
            $cardData->season = Season::findOrFail($card->season_id)->first();
        
            array_push($cardArr, $cardData);
        }

        return response()->json(
            [
                'cards' => $cardArr,
                'reverse' => $reverse
            ]
        );
    }
}