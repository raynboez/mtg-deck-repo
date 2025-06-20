<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'file'=> 'required|file|mimes:csv,txt',
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

        

        $fileContents = file($file);
        $importedCards = 0;
        $failedCards = [];
        
        foreach($fileContents as $lineNumber => $line) 
        {
            $line = trim($line);
            if(empty($line))
            {
                continue;
            }

            $cardData = $this->parseCardLine($line);
            if(!$cardData)
            {
                $failedCards[] = 
                [
                    'line' => $lineNumber + 1,
                    'content' => $line,
                    'reason' => 'Invalid Format'
                ];
                continue;
            }

            try
            {
                $card = $this->findCard($cardData);
                if(!$card)
                {
                    $failedCards[] = 
                    [
                        'line' => $lineNumber + 1,
                        'content' => $line,
                        'reason' => 'Scryfall Error'
                    ];
                    continue;                    
                }

                DeckCard::create([
                    'deck_id' => $deck->deck_id,
                    'card_id' => $card->card_id,
                    'is_main_deck' => true,
                    'quantity' => $cardData['quantity']
                ]);

                $importedCards++;

                usleep(100000);
            }
            catch (\Exception $e)
            {
                $failedCards[] =
                [
                    'line' => $lineNumber +1,
                    'content' => $line,
                    'reason' => 'processing error: ' . $e->getMessage()
                ];
                continue;
            }
        }

        return response()->json([
            'message' => 'Deck import complete',
            'stats' => [
                'total_lines' => count($fileContents),
                'imported_cards' => $importedCards,
                'failed_cards' => count($failedCards)
            ],
            #'deck' => DB::table('deck_cards')->join('cards', 'deck_cards.card_id', 'cards.card_id')->select('quantity', 'card_name')->where('deck_id', $deck->deck_id),
            'failures' => $failedCards
        ]);
    }

    protected function parseCardLine($line)
    {
        $pattern = '/^(\d+)\s+(.*?)\s+\(([A-Z0-9]+)\)\s+(\d+)(?:\s+\*F\*)?$/';

        if(!preg_match($pattern, $line, $matches)) 
        {
            $alt = '/^(\d+)\s+(.*?)\s+\(([A-Z0-9]+)\)\s+(\d+)(?:\s+\((F)\))?$/';
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
        
        if(!$scryfallCard)
        {
            return null;
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
}
