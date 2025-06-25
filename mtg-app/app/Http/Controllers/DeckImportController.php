<?php

namespace App\Http\Controllers;

use App\Models\ReverseCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Deck;
use App\Models\Card;
use App\Models\DeckCard;
use App\Services\ScryfallService;


class DeckImportController extends Controller
{

    protected $scryfall;

    public function __construct(ScryfallService $scryfall)
    {
        $this->scryfall = $scryfall;
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'=> 'nullable|file|mimes:csv,txt',
            'text'=> 'nullable|string',
            'deck_name'=>'required|string',
            'deck_description'=>'nullable|string'
        ]);

        $user = auth()->user();
        $file = $request->file('file');
        $deckName = $request->input('deck_name');
        $deckDescription = $request->input('deck_description');

        $deck = Deck::create([
            'deck_name' => $deckName,
            'description' => $deckDescription,
            'user_id' => $user->user_id
        ]);

        

        $lines = $this->getDeckListLines($request);
        $parsedCards = $this->parseDecklist($lines);
        $importedCards = 0;
        $potentialCommanders = [];
        $failedCards = [];
        
        foreach($parsedCards as $cardData) 
        {

            try
            {
                $card = $this->findCard($cardData);
                Log::info($card);
                if(!$card)
                {
                    $failedCards[] = 
                    [
                        'name' => $cardData['name'],
                        'section' => $cardData['section'],
                        'reason' => 'Scryfall Error - Card not found: ' . $cardData['name']
                    ];
                    continue;                    
                }
                if(str_contains($card->type_line,'Legendary')){
                    array_push($potentialCommanders, $card);
                }                
                DeckCard::create([
                    'deck_id' => $deck->deck_id,
                    'card_id' => $card->card_id,
                    'is_main_deck' => $cardData['section'] === 'main',
                    'quantity' => $cardData['quantity']
                ]);

                $importedCards++;

                usleep(100000);
            }
            catch (\Exception $e)
            {
                $failedCards[] =
                [
                    'name' => $cardData['name'],
                    'section' => $cardData['section'],
                    'reason' => 'processing error: ' . $e->getMessage()
                ];
                continue;
            }
        }



        return response()->json([
            'message' => 'Deck import complete',
            'stats' => [
                'total_lines' => count($lines),
                'imported_cards' => $importedCards,
                'failed_cards' => count($failedCards)
            ],
            'failures' => $failedCards,
            'deck_id' => $deck->deck_id,
            'potential_commanders' => $potentialCommanders
        ]);
    }

    protected function getDeckListLines(Request $request): array
    {
        if($request->hasFile('file'))
        {
            return file($request->file('file')->path(), FILE_IGNORE_NEW_LINES);
        }
        if($request->filled('text'))
        {
            return explode("\n", $request->input('text'));
        }
        throw new \InvalidArgumentException('No decklist provided');
    }

    protected function parseDecklist(array $lines): array
{
    $cards = [];
    $currentSection = 'main';
    
    foreach ($lines as $lineNumber => $line) {
        $line = trim($line);
        
        if (empty($line)) {
            continue;
        }
        
        if (preg_match('/^SIDEBOARD|SB:/i', $line)) {
            $currentSection = 'sideboard';
            continue;
        }
        
        if (str_starts_with($line, '//')) {
            continue;
        }
        
        $cardData = $this->parseCardLine($line);
        if (!$cardData) {
            continue;
        }
        
        $cardData['section'] = $currentSection;
        $cards[] = $cardData;
    }
    
    return $cards;
}


    protected function parseCardLine($line)
    {
        $pattern = '/^(\d+)\s+(.*?)\s+\(([A-Za-z0-9]+)\)\s+([A-Za-z0-9]*-?\d+)(?:\s+\*[FE]\*)?$/';

        if(!preg_match($pattern, $line, $matches)) 
        {
            $alt = '/^(\d+)\s+(.*?)\s+\(([A-Za-z0-9]+)\)\s+([A-Za-z0-9]*-?\d+)(?:\s+\(([FE])\))?$/';
            if(!preg_match($alt, $line, $matches))
            {
                return null;
            }
        }

        return 
        [
            'quantity' => (int)$matches[1],
            'name' => trim($matches[2]),
            'set' => $matches[3],
            'collector_number' => $matches[4],
            'is_foil' => isset($matches[5]) && strtoupper($matches[5]) === 'F'
        ];
    }

    protected function findCard(array $cardData)
    {
        $card = Card::where('card_name', $cardData['name'])
            ->when($cardData['set'], function($query) use ($cardData) 
            {
                return $query->where('set', $cardData['set']);
            })
            ->when($cardData['collector_number'], function($query) use ($cardData)
            {
                return $query->where('collector_number', $cardData['collector_number']);
            })
            ->first();

        if($card)
        {
            return $card;
        }

        $scryfallCard = $this->scryfall->searchCard
        (
            $cardData['name'],
            $cardData['set'],
            $cardData['collector_number']
        );
        Log::info($cardData['name']);
        Log::info($scryfallCard);
        if(!$scryfallCard)
        {
            return null;
        }
        
        if(isset($scryfallCard['card_faces'])){
            $faceCard = Card::create([
                'card_name' =>$scryfallCard['card_faces'][0]['name'],
                'mana_cost' => $scryfallCard['card_faces'][0]['mana_cost'],
                'cmc' => $scryfallCard['cmc'],
                'type_line' => $scryfallCard['card_faces'][0]['type_line'],
                'oracle_text' => $scryfallCard['card_faces'][0]['oracle_text'],
                'colours' => implode(',' ,$scryfallCard['card_faces'][0]['colors']),
                'colour_identity' => implode(',' ,$scryfallCard['color_identity']),
                'image_url' => $scryfallCard['card_faces'][0]['image_uris']['normal'],
                'scryfall_uri' => $scryfallCard['scryfall_uri'],
                'set' => $scryfallCard['set'],
                'collector_number' => $scryfallCard['collector_number'],
                'is_gamechanger' => $scryfallCard['game_changer'],
                'oracle_id' => $scryfallCard['oracle_id']
            ]);
            $reverseCard = ReverseCard::create([
                'card_name' =>$scryfallCard['card_faces'][1]['name'],
                'face_card_id' => $faceCard['card_id'],
                'mana_cost' => $scryfallCard['card_faces'][1]['mana_cost'],
                'cmc' => $scryfallCard['cmc'],
                'type_line' => $scryfallCard['card_faces'][1]['type_line'],
                'oracle_text' => $scryfallCard['card_faces'][1]['oracle_text'],
                'colours' => implode(',' ,$scryfallCard['card_faces'][1]['colors']),
                'colour_identity' => implode(',' ,$scryfallCard['color_identity']),
                'image_url' => $scryfallCard['card_faces'][1]['image_uris']['normal'],
                'scryfall_uri' => $scryfallCard['scryfall_uri'],
                'set' => $scryfallCard['set'],
                'collector_number' => $scryfallCard['collector_number'],
                'is_gamechanger' => $scryfallCard['game_changer'],
                'oracle_id' => $scryfallCard['oracle_id']
            ]);
            Card::where('card_id', $faceCard['card_id'])->update(['reverse_card_id' => $reverseCard['card_id']]);
            return $faceCard;
        }

        return Card::create([
            'card_name' =>$scryfallCard['name'],
            'mana_cost' => $scryfallCard['mana_cost'],
            'cmc' => $scryfallCard['cmc'],
            'type_line' => $scryfallCard['type_line'],
            'oracle_text' => $scryfallCard['oracle_text'],
            'colours' => implode(',' ,$scryfallCard['colors']),
            'colour_identity' => implode(',' ,$scryfallCard['color_identity']),
            'image_url' => $scryfallCard['image_uris']['normal'],
            'scryfall_uri' => $scryfallCard['scryfall_uri'],
            'set' => $scryfallCard['set'],
            'collector_number' => $scryfallCard['collector_number'],
            'is_gamechanger' => $scryfallCard['game_changer'],
            'oracle_id' => $scryfallCard['oracle_id']
        ]);
    }

    public function update(Request $request, $id)
    {
        Log::info($id);
        $deck = Deck::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'commanders' => 'required|array',
            'power_level' => 'required|integer|between:1,5'
        ]);
        
        DB::table('decks')->where('deck_id', $id)->update(
            [
                'deck_name' => $validated['name'],
                'description' => $validated['description'],
                'power_level' => $validated['power_level']
            ]
            );
        foreach ($validated['commanders'] as $commanderId) {
            DB::table('deck_cards')->where('card_id', $commanderId)->update(
            [
                'is_commander' => true
            ]);
        }
        

        return response()->json($deck);
    }
}
