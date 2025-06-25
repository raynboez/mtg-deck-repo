<?php
namespace App\Services;

use App\Models\Deck;
use App\Models\Card;

class DeckExportService
{
    public function generateExportText(Deck $deck): string
    {
        $exportLines = [];
        
        // Add About section with deck name
        $exportLines[] = "About";
        $exportLines[] = "Name $deck->deck_name";
        $exportLines[] = ""; // Empty line

        // Load cards with pivot data if not already loaded
        if (!$deck->relationLoaded('cards')) {
            $deck->load('cards');
        }

        // Process commanders first
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

        // Group non-commander cards by name and set
        $mainDeckCards = $deck->cards->reject(function ($card) {
            return $card->pivot->is_commander ?? false;
        });

        $cardGroups = $mainDeckCards->groupBy(function ($card) {
            return $card->name . '|' . ($card->set_code ?? '');
        });

        // Add Deck section header
        $exportLines[] = "Deck";

        // Process main deck cards
        foreach ($cardGroups as $group) {
            $card = $group->first();
            $totalQuantity = $group->sum(function ($card) {
                return $card->pivot->quantity ?? 1;
            });
            
            $exportLines[] = $this->formatCardLine($totalQuantity, $card);
        }

        return implode("\n", $exportLines);
    }

    protected function formatCardLine(int $quantity, Card $card): string
    {
        $setInfo = '';
        if ($card->set_code && $card->collector_number) {
            $setInfo = " ({$card->set_code}) {$card->collector_number}";
        }
        return "{$quantity} {$card->name}{$setInfo}";
    }
}