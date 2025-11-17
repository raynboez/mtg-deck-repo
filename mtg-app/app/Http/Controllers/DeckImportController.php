<?php

namespace App\Http\Controllers;

use App\Models\ReverseCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use \Illuminate\Support\Str;
use App\Models\Deck;
use App\Models\Card;
use App\Models\OverriddenCard;
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
        set_time_limit(0);
        $request->validate([
            'url'=> 'nullable|string',
            'text'=> 'nullable|string',
            'deck_name'=>'required|string',
            'deck_description'=>'nullable|string'
        ]);

        $user = auth()->user();
        $deckName = $request->input('deck_name');
        $deckDescription = $request->input('deck_description');
        if($request->filled('url')){
            $deck = Deck::create([
                'deck_name' => $deckName,
                'description' => $deckDescription,
                'user_id' => $user->user_id,
                'url' => $request->input('url')
            ]);
        } else {
            $deck = Deck::create([
                'deck_name' => $deckName,
                'description' => $deckDescription,
                'user_id' => $user->user_id
            ]);
        }        

        $parsedCards = $this->getDeckListLines($request);
        $importedCards = 0;
        $potentialCommanders = [];
        $failedCards = [];
        
        foreach($parsedCards as $cardData) 
        {

            try
            {
                $card = $this->findCard($cardData);
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
                if($cardData['section'] !== 'main'){
                    continue;
                    //skip sideboard cards for now
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
                'imported_cards' => $importedCards,
                'failed_cards' => count($failedCards)
            ],
            'failures' => $failedCards,
            'deck_id' => $deck->deck_id,
            'potential_commanders' => $potentialCommanders,
        ]);
    }

    protected function getDeckListLines(Request $request): array
    {
        $lines = '';
        if($request->hasFile('file'))
        {
            $lines = file($request->file('file')->path(), FILE_IGNORE_NEW_LINES);
        }
        else if($request->filled('url'))
        {
            return $this->importDeckFromUrl($request->input('url'));
        }
        else if($request->filled('text'))
        {
            $lines = explode("\n", $request->input('text'));
        } else {
            throw new \InvalidArgumentException('No decklist provided');
        }
        return $this->parseDecklist($lines);
    }

    protected function importDeckFromUrl(string $url): array
    {
        $parsedUrl = parse_url($url);
        if(!$parsedUrl || !isset($parsedUrl['host']))
        {
            throw new \InvalidArgumentException('Invalid URL provided');
        }

        $host = Str::lower($parsedUrl['host']);
        if(Str::contains($host, 'archidekt.com'))
        {
            return $this->parseDeckFromArchidekt($url);
        }

        throw new \InvalidArgumentException('Unsupported URL host: ' . $host);
    }

    protected function parseDeckFromArchidekt(string $deckUrl): array
    {
        try {
            $deckId = $this->extractDeckId($deckUrl);
            
            if (!$deckId) {
                throw new \Exception('Invalid Archidekt deck URL');
            }
            
            $apiUrl = "https://archidekt.com/api/decks/{$deckId}/";
            
            $response = Http::timeout(30)->get($apiUrl);
            
            if (!$response->successful()) {
                throw new \Exception('Failed to fetch deck data from Archidekt API');
            }
            
            $deckData = $response->json();
            
            return $this->parseDeckJson($deckData);
            
        } catch (\Exception $e) {
            Log::error('Error parsing Archidekt deck: ' . $e->getMessage());
            return [];
        }
    }

    protected function extractDeckId(string $url): ?string
    {
        if (preg_match('/archidekt\.com\/decks\/(\d+)/', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    protected function parseDeckJson(array $deckData): array
    {
        $cards = [];
        
        if (!isset($deckData['cards'])) {
            return $cards;
        }
        
        foreach ($deckData['cards'] as $cardItem) {
            $cardData = $this->parseCardJson($cardItem);
            if ($cardData) {
                $cards[] = $cardData;
            }
        }
        
        return $cards;
    }

    protected function parseCardJson(array $cardItem): ?array
    {
        if (!isset($cardItem['card'], $cardItem['categories'])) {
            return null;
        }
        
        $card = $cardItem['card'];
        $categories = $cardItem['categories'];
        $quantity = $cardItem['quantity'] ?? 1;
        
        $section = $this->determineSection($categories);
        $oracleCard = $card['oracleCard'] ?? $card;
        $printing = $card['printing'] ?? [];
        
        return [
            'quantity' => (int)$quantity,
            'name' => trim($oracleCard['name'] ?? ''),
            'set' => $printing['setcode'] ?? ($printing['set'] ?? ''),
            'collector_number' => $printing['collectorNumber'] ?? '',
            'is_foil' => $cardItem['foil'] ?? false,
            'section' => $section
        ];
    }

    protected function determineSection(array $categories): string
    {
        
        // Archidekt categories: 1=Commander, 2=Companion, 3=Main, 4=Sideboard, 5=Maybeboard, etc.
        foreach ($categories as $category) {
            switch($category) {
                case 'Sideboard':
                    return 'Sideboard';
                case 'Maybeboard':
                    return 'Maybeboard';
            }
        }
        
        return 'main';
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
        $pattern = '/^(\d+)\s+(.*?)\s+\(([A-Za-z0-9]+)\)\s+([A-Za-z0-9]*-?[A-Za-z0-9]+)(?:\s+\*[FE]\*)?$/';

        if(!preg_match($pattern, $line, $matches)) 
        {
            $alt = '/^(\d+)\s+(.*?)\s+\(([A-Za-z0-9]+)\)\s+([A-Za-z0-9]*-?[A-Za-z0-9]+)(?:\s+\(([FE])\))?$/';
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

    public function findCard(array $cardData)
    {   
        if(!isset($cardData['name']))
        {
            $cardData['name'] = $cardData['card_name'];
        }
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

        if(isset($scryfallCard['card_faces']) && !(strcmp($scryfallCard['layout'], "adventure")==0) && !(strcmp($scryfallCard['layout'], "split")==0) && !(strcmp($scryfallCard['layout'], "reversible_card")==0)){
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
        if(isset($scryfallCard['card_faces']) && (strcmp($scryfallCard['layout'], "split")==0)){
            $faceCard = Card::create([
                'card_name' =>$scryfallCard['card_faces'][0]['name'],
                'mana_cost' => $scryfallCard['card_faces'][0]['mana_cost'],
                'cmc' => $scryfallCard['cmc'],
                'type_line' => $scryfallCard['card_faces'][0]['type_line'],
                'oracle_text' => $scryfallCard['card_faces'][0]['oracle_text'],
                'colours' => implode(',' ,$scryfallCard['colors']),
                'colour_identity' => implode(',' ,$scryfallCard['color_identity']),
                'image_url' => $scryfallCard['image_uris']['normal'],
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
                'colours' => implode(',' ,$scryfallCard['colors']),
                'colour_identity' => implode(',' ,$scryfallCard['color_identity']),
                'image_url' => $scryfallCard['image_uris']['normal'],
                'scryfall_uri' => $scryfallCard['scryfall_uri'],
                'set' => $scryfallCard['set'],
                'collector_number' => $scryfallCard['collector_number'],
                'is_gamechanger' => $scryfallCard['game_changer'],
                'oracle_id' => $scryfallCard['oracle_id']
            ]);
            Card::where('card_id', $faceCard['card_id'])->update(['reverse_card_id' => $reverseCard['card_id']]);
            return $faceCard;
        }

        if((strcmp($scryfallCard['layout'], "adventure")==0)){
            return Card::create([
            'card_name' =>$scryfallCard['name'],
            'mana_cost' => $scryfallCard['mana_cost'],
            'cmc' => $scryfallCard['cmc'],
            'type_line' => $scryfallCard['type_line'],
            'oracle_text' => $scryfallCard['card_faces'][0]['oracle_text'] . ' // ' . $scryfallCard['card_faces'][1]['oracle_text'],
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

        if((strcmp($scryfallCard['layout'], "reversible_card")==0)){
            return Card::create([
            'card_name' =>$scryfallCard['card_faces'][0]['name'],
            'mana_cost' => $scryfallCard['card_faces'][0]['mana_cost'],
            'cmc' => $scryfallCard['card_faces'][0]['cmc'],
            'type_line' => $scryfallCard['card_faces'][0]['type_line'],
            'oracle_text' => $scryfallCard['card_faces'][0]['oracle_text'],
            'colours' => implode(',' ,$scryfallCard['card_faces'][0]['colors']),
            'colour_identity' => implode(',' , $scryfallCard['color_identity']),
            'image_url' => $scryfallCard['card_faces'][0]['image_uris']['normal'],
            'scryfall_uri' => $scryfallCard['scryfall_uri'],
            'set' => $scryfallCard['set'],
            'collector_number' => $scryfallCard['collector_number'],
            'is_gamechanger' => $scryfallCard['game_changer'],
            'oracle_id' => $scryfallCard['card_faces'][0]['oracle_id']
        ]);
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

    public function addCard(Request $request, $id){
        $validated = $request->validate([
            'scryfallData' => 'required',
            'amount' => 'required|integer'
        ]);
        $deck = Deck::findOrFail($id);
        $cardData = $validated['scryfallData'];
        Log::info('Adding card: ' . json_encode($cardData));
        $card = $this->findCard($cardData);
        Log::info('Found card: ' . json_encode($card));
        if(!$card)
                {
                    response()->json([
                'success' => false,
                'message' => 'Failed to add card to deck',
                'error' => 'Internal server error'
                    ], 500);            
        
                }
        
        
        $existingDeckCard = DeckCard::where('deck_id', $deck->deck_id)
            ->where('card_id', $card->card_id)
            ->first();

        if ($existingDeckCard) {
            $newQuantity = $existingDeckCard->quantity + $validated['amount'];
            
            $existingDeckCard->update([
                'quantity' => $newQuantity
            ]);
            
            $success = $existingDeckCard;
        } else {
            $success = DeckCard::create([
                'deck_id' => $deck->deck_id,
                'card_id' => $card->card_id,
                'is_main_deck' => true,
                'quantity' => $validated['amount']
            ]);
            
        }
        if($success){
            return response()->json([
                'success' => true,
                'message' => 'Card successfully added',
                'data' => [
                    'card' => $card->card_name,
                    'quantity' =>  $validated['amount'],
                    'action' => 'added'
                ]
            ], 201);
        } else{
            {
                    response()->json([
                        'success' => false,
                        'message' => 'Failed to add card to deck',
                        'error' => 'Internal server error'
                    ], 500);            
                }  
        }
        
    }


    public function removeCard(Request $request, $id){
        $validated = $request->validate([
            'card_id' => 'required|integer',
            'amount' => 'required|integer'
        ]);
        $deck = Deck::findOrFail($id);
        $card = Card::findOrFail($validated['card_id']);
        $deckcard = DeckCard::where('deck_id', $deck->deck_id)->where('card_id', $card->card_id)->firstOrFail();
        if($deckcard->quantity > $validated['amount']){
            $deckcard->quantity= $deckcard->quantity - $validated['amount'];
            $deckcard->save();
        } else {
            DeckCard::where('deck_id', $deck->deck_id)->where('card_id', $card->card_id)->delete();
        }
        return response()->json([
                'success' => true,
                'message' => 'Card successfully removed',
                'data' => [
                    'card' => $card->card_name,
                    'quantity' =>  $validated['amount'],
                    'action' => 'removed'
                ]
            ], 201);
    }

    public function update(Request $request, $id)
    {
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'url' => 'nullable|string',
            'commanders' => 'array',
            'power_level' => 'required|integer|between:1,5'
        ]);
        $deck = Deck::findOrFail($id);
        
        DB::table('decks')->where('deck_id', $id)->update(
            [
                'deck_name' => $validated['name'],
                'description' => $validated['description'],
                'power_level' => $validated['power_level'],
                'url' => $validated['url']
            ]
            );
        DB::table('deck_cards')
            ->where('deck_id', $id)
            ->update(['is_commander' => false]);
        $identityArray = [];
        foreach ($validated['commanders'] as $commanderId) {
            DB::table('deck_cards')
            ->where('card_id', $commanderId)
            ->where('deck_id', $id)
            ->update(
            [
                'is_commander' => true
            ]);
            $commander = Card::findOrFail($commanderId);
            $cardIdentity = $commander->colour_identity ? explode(',', $commander->colour_identity) : [];
            $identityArray = array_unique(array_merge($identityArray, $cardIdentity));
        }
        $deck->colour_identity = $this->getIdentity($identityArray);
        $deck->colours = $identityArray;
        $deck->save();
        
        return response()->json($deck);

    }
    protected function getIdentity(array $colorIdentity){
        $normalized = $this->normalizeColorIdentity($colorIdentity);
        
        return $this->factionNames[$normalized] ?? $this->generateDescriptiveName($colorIdentity);
    }

    protected function normalizeColorIdentity(array $colorIdentity)
    {

        if (empty($colorIdentity)) {
            return '';
        }

        $uniqueColors = array_unique($colorIdentity);
        $sorted = $this->sortColorsWubrg($uniqueColors);
        
        return implode('', $sorted);
    }

    protected function sortColorsWubrg(array $colors)
    {
        $wubrgOrder = ['W', 'U', 'B', 'R', 'G'];
        
        return array_filter($wubrgOrder, function($color) use ($colors) {
            return in_array($color, $colors);
        });
    }

    protected function generateDescriptiveName(array $colors)
    {
        if (empty($colors)) {
            return 'Colorless';
        }

        $sortedColors = $this->sortColorsWubrg($colors);
        $colorNames = array_map(fn($color) => $this->colorNames[$color] ?? $color, $sortedColors);
        
        if (count($sortedColors) === 1) {
            return $colorNames[0];
        }
        
        return implode('-', $colorNames);
    }

    public function getColorNames(array $colorIdentity)
    {
        if (empty($colorIdentity)) {
            return 'Colorless';
        }

        $sortedColors = $this->sortColorsWubrg($colorIdentity);
        $names = array_map(fn($color) => $this->colorNames[$color] ?? $color, $sortedColors);
        
        return implode(', ', $names);
    }

    public function getNormalizedColorString(array $colorIdentity)
    {
        return $this->normalizeColorIdentity($colorIdentity);
    }


    protected $factionNames = [
        'W' => 'MonoWhite',
        'U' => 'MonoBlue',
        'B' => 'MonoBlack',
        'R' => 'MonoRed',
        'G' => 'MonoGreen',
        
        'WU' => 'Azorius',
        'UB' => 'Dimir',
        'BR' => 'Rakdos',
        'RG' => 'Gruul',
        'WG' => 'Selesnya',
        'WB' => 'Orzhov',
        'BG' => 'Golgari',
        'UG' => 'Simic',
        'UR' => 'Izzet',
        'WR' => 'Boros',
        
        'WUB' => 'Esper',
        'UBR' => 'Grixis',
        'BRG' => 'Jund',
        'WRG' => 'Naya',
        'WUG' => 'Bant',
        'WBG' => 'Abzan',
        'WUR' => 'Jeskai',
        'UBG' => 'Sultai',
        'WBR' => 'Mardu',
        'URG' => 'Temur',
        
        'WUBR' => 'Yore-Tiller',
        'UBRG' => 'Glint-Eye',
        'WBRG' => 'Dune-Brood',
        'WURG' => 'Ink-Treader',
        'WUBG' => 'Witch-Maw',
        
        'WUBRG' => 'Five-Color',
        
        '' => 'Colorless'
    ];

    protected $colorNames = [
        'W' => 'White',
        'U' => 'Blue',
        'B' => 'Black',
        'R' => 'Red',
        'G' => 'Green'
    ];

    public function updateFromUrl(int $deckId)
    {
        $deck = Deck::findOrFail($deckId);
        if(!$deck->url || !Str::contains($deck->url, 'archidekt.com'))
        {
            throw new \InvalidArgumentException('Deck does not have a valid Archidekt URL');
        }

        $parsedCards = $this->importDeckFromUrl($deck->url);
        if(empty($parsedCards))
        {
            throw new \RuntimeException('Failed to re-import deck from Archidekt');
        }

        DeckCard::where('deck_id', $deck->deck_id)->delete();

        $importedCards = 0;
        $failedCards = [];

        foreach($parsedCards as $cardData) 
        {
            try
            {
                $card = $this->findCard($cardData);
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
            'message' => 'Deck re-import complete',
            'stats' => [
                'imported_cards' => $importedCards,
                'failed_cards' => count($failedCards)
            ],
            'failures' => $failedCards,
            'deck_id' => $deck->deck_id
        ]);
    }

    public function override(Request $request, $deck_id)
    {
        $request->validate([
            'overrides' => 'required|array',
        ]);

        $deck = Deck::findOrFail($deck_id);

        $seasonId = app(SeasonController::class)->getActiveSeasonId();
        if (is_object($seasonId) && method_exists($seasonId, 'getData')) {
            $seasonId = $seasonId->getData();
        }

        $overrides = $request->input('overrides');
        $results = [];

        foreach ($overrides as $base_card_id => $override_card_data) {
            Log::info($request->all());

            $overrideCard = $this->findCard($override_card_data);

            $overridden = OverriddenCard::updateOrCreate(
                [
                    'season_id' => $seasonId,
                    'deck_id' => $deck->deck_id,
                    'base_card_id' => $base_card_id,
                ],
                [
                    'override_card_id' => $overrideCard->card_id,
                ]
            );

            $results[] = [
                'base_card_id' => $base_card_id,
                'override_card_id' => $overrideCard->card_id,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Overrides saved.',
            'results' => $results,
        ]);
    }
}
