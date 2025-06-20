<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScryfallService
{
    protected $baseUrl = 'https://api.scryfall.com';

    public function searchCard(string $name, string $set = null, string $number = null)
    {
        try 
        {
            $query = "!\"{$name}\"";
            if($set)
            {
                $query .=" set:{$set}";
            }
            if($number)
            {
                $query .=" number:{$number}";
            }

            $response = Http::get("{$this->baseUrl}/cards/search",
            [
                'q' => $query,
                'unique' => 'prints'
            ]);

            if($response->successful())
            {
                #Log::info($response);;
                $data = $response->json();
                if ($data['total_cards'] > 0)
                {
                    if($set && $number)
                    {
                        foreach ($data['data'] as $card)
                        {   
                            if($card['set'] === strtolower($set) && $card['collector_number'] === $number)
                            {
                                return $card;
                            }
                        }
                    }
                }
                return $data['data'][0];
            }
            
        }
        catch(\Exception $e)
        {
            Log::error("Scryfall API error: " . $e->getMessage());
            return null;
        }
    
    }

}