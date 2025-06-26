<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScryfallService
{
    protected $baseUrl = 'https://api.scryfall.com';

    public function searchCard(string $name, ?string $set = null, ?string $number = null)
    {
        try {
            $normalizedName = $this->normalizeCardName($name);
            
            $exactMatch = $this->tryExactMatch($normalizedName, $set, $number);
            if ($exactMatch) {
                return $exactMatch;
            }

            if (str_contains($normalizedName, '//')) {
                $firstFace = trim(explode('//', $normalizedName)[0]);
                $firstFaceMatch = $this->tryExactMatch($firstFace, $set, $number);
                if ($firstFaceMatch) {
                    return $firstFaceMatch;
                }
            }

            $fuzzySearch = $this->tryFuzzySearch($normalizedName, $set, $number);
            if($fuzzySearch){
                return $fuzzySearch;
            } else {
                return $this->tryFuzzySearch($normalizedName, null, null);
            }
        } catch (\Exception $e) {
            Log::error("Scryfall API error searching for {$name}: " . $e->getMessage());
            return null;
        }
    }

    protected function tryExactMatch(string $name, ?string $set, ?string $number)
    {
        $queryParams = ['exact' => $name];
        if ($set) {
            $queryParams['set'] = strtolower($set);
        }
        
        $response = Http::get("{$this->baseUrl}/cards/named", $queryParams);
        
        if ($response->successful()) {
            $card = $response->json();
            if (!$number || $card['collector_number'] === $number) {
                return $card;
            }
        }
        
        return null;
    }

    protected function tryFuzzySearch(string $name, ?string $set, ?string $number)
    {
        $query = "!\"{$name}\"";
        if ($set) {
            $query .= " set:{$set}";
        }
        if ($number) {
            $query .= " number:{$number}";
        }

        $response = Http::get("{$this->baseUrl}/cards/search", [
            'q' => $query,
            'unique' => 'prints',
            'order' => 'released',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if ($data['total_cards'] > 0) {
                if ($set && $number) {
                    foreach ($data['data'] as $card) {
                        if ($card['set'] === strtolower($set) && 
                            $card['collector_number'] === $number) {
                            return $card;
                        }
                    }
                }
                return $data['data'][0];
            }
        }
        
        return null;
    }

    protected function normalizeCardName(string $name): string
    {
        // Remove set code
        $name = preg_replace('/\s*\([^)]+\)\s*$/', '', $name);
        // Remove collector numbers
        $name = preg_replace('/\s*\d+\s*$/', '', $name);
        // Remove foil flag
        $name = preg_replace('/\s*\*[^*]+\*\s*$/', '', $name);
        
        return trim($name);
    }
}