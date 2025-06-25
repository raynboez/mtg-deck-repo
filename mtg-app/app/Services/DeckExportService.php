<?php
namespace App\Services;

use App\Models\Deck;
use App\Models\Card;

class DeckExportService
{
    public function generateExportText(Deck $deck): string
    {
        $exportLines = [];
        
        // Add About section
        $exportLines[] = "About";
        $exportLines[] = "Name " . $deck->name;
        $exportLines[] = ""; // Empty line

        // Load cards with pivot data if not already loaded
        if (!$deck->relationLoaded('cards')) {
            $deck->load('cards');
        }

        // Process commanders
        $commanders = $deck->cards->filter(function ($card) {
            return $card->pivot->is_commander ?? false;
        });

        if ($commanders->isNotEmpty()) {
            $exportLines[] = "Commander";
            foreach ($commanders as $commander) {
                $exportLines[] = $this->formatCardLine(
                    $commander->pivot->quantity ?? 1,
                    $commander
                );
            }
            $exportLines[] = ""; // Empty line after commanders
        }

        // Process main deck cards
        $mainDeckCards = $deck->cards->reject(function ($card) {
            return $card->pivot->is_commander ?? false;
        });

        $exportLines[] = "Deck";
        
        // Sort cards alphabetically
        $sortedCards = $mainDeckCards->sortBy(function ($card) {
            return $card->name;
        });

        foreach ($sortedCards as $card) {
            $exportLines[] = $this->formatCardLine(
                $card->pivot->quantity ?? 1,
                $card
            );
        }

        return implode("\n", $exportLines);
    }

    protected function formatCardLine(int $quantity, Card $card): string
    {
        $setInfo = '';
        if ($card->set && $card->collector_number) {
            $setInfo = " ({$card->set}) {$card->collector_number}";
        }
        return "{$quantity} {$card->card_name}{$setInfo}";
    }
}