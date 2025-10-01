<?php
namespace App\Services;

use App\Models\Deck;
use App\Models\Card;
use App\Models\OverriddenCard;
use App\Models\DeckCard;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\SeasonController;

class DeckExportService
{
    public function generateExportText(Deck $deck): string
    {
        $exportLines = [];
        $seasonId = app(SeasonController::class)->getActiveSeasonId();
        
        $exportLines[] = "About";
        $exportLines[] = "Name " . $deck->deck_name;
        $exportLines[] = ""; 

        $deckCards = DeckCard::where('deck_id', $deck->deck_id)
            ->with('card')
            ->get();

        $processedCards = [];

        foreach ($deckCards as $deckCard) {
            $card = Card::find($deckCard->card_id);
            
            $overriddenCard = OverriddenCard::where('season_id', $seasonId)
                ->where('deck_id', $deck->deck_id)
                ->where('base_card_id', $card->card_id)
                ->first();

            if ($overriddenCard) {
                $card = Card::findOrFail($overriddenCard->override_card_id);
            }
            
            $processedCards[] = [
                'card' => $card,
                'quantity' => $deckCard->quantity ?? 1,
                'is_commander' => $deckCard->is_commander ?? false
            ];
        }

        $commanders = array_filter($processedCards, function ($item) {
            return $item['is_commander'];
        });

        if (!empty($commanders)) {
            $exportLines[] = "Commander";
            foreach ($commanders as $commander) {
                $exportLines[] = $this->formatCardLine(
                    $commander['quantity'],
                    $commander['card']
                );
            }
            $exportLines[] = "";
        }

        $mainDeckCards = array_filter($processedCards, function ($item) {
            return !$item['is_commander'];
        });

        $exportLines[] = "Deck";
        
        usort($mainDeckCards, function ($a, $b) {
            return strcmp($a['card']->card_name, $b['card']->card_name);
        });

        foreach ($mainDeckCards as $cardItem) {
            $exportLines[] = $this->formatCardLine(
                $cardItem['quantity'],
                $cardItem['card']
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