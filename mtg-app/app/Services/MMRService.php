<?php
namespace App\Services;

use App\Models\MatchParticipant;
use Illuminate\Support\Facades\Log;
class MMRService
{

    const K_FACTOR_BASE = 25;
    const K_FACTOR_VOLATILE = 40;

    const PLACEMENT_MATCHES = 10;
    const BASE_POINTS_6 = [
        1 => 15,
        2 => 10,
        3 => 5,
        4 => 0,
        5 => -5,
        6 => -10
    ];
    const BASE_POINTS_5 = [
        1 => 10,
        2 => 5,
        3 => 0,
        4 => -5,
        5 => -10,
    ];
    const BASE_POINTS_4 = [
        1 => 10,
        2 => 5,
        3 => 0,
        4 => -5,
    ];
    const BASE_POINTS_3 = [
        1 => 5,
        2 => 0,
        3 => -5,
    ];
    const BASE_POINTS_2 = [
        1 => 5,
        2 => 0,
    ];
    
    const POSITION_BONUS = 3;
    const POSITION_PENALTY = 3;

    public function calculateMatchMMR(array $playersData, $format)
    {
        $players = collect($playersData);
        $playerCount = $players->count();

        $playersWithPosition = $this->assignFinishingPositions($players);
        $playersWithMMR = $this->getCurrentMMRForPlayers($playersWithPosition, $format);
        
        $sortedByMMR = $playersWithMMR->sortByDesc('current_mmr');
        $mmrChanges = [];
        $averageMMR = $sortedByMMR->avg('current_mmr');


        foreach ($sortedByMMR as $player) {
            $position = $player['position'];
            /*
            $basePoints = $this->getPointsForPosition($position, $playerCount);
            
            $expectedPosition = $sortedByMMR->values()->search(function($p) use ($player) {
                return $p['user_id'] === $player['user_id'];
            }) + 1;
            $expectedPoints = $this->getPointsForPosition($expectedPosition, $playerCount);


            

            $adjustment = 0;
            if ($position < $expectedPosition) {
                $adjustment = self::POSITION_BONUS;
            } elseif ($position > $expectedPosition) {
                $adjustment = -self::POSITION_PENALTY;
            }


            $totalChange = $basePoints + $adjustment;
            $mmrChanges[$player['user_id']] = [
                'change' => $totalChange,
                'position' => $position,
                'base_points' => $basePoints,
                'adjustment' => $adjustment
            ];
            */

            $basePoints = $this->getPointsForPosition($position, $playerCount);
            if($player['is_winner']){
                $basePoints++;
            }
            if($player['first_blood']){
                $basePoints++;
            }
            if($player['motm']){
                $basePoints++;
            }

            $gamesPlayed = $this->getGamesPlayed($player['user_id'], $format);
            if ($gamesPlayed < self::PLACEMENT_MATCHES) {
                
                $expectedPosition = ($playerCount + 1) / 2;
            } else {
                $expectedPosition = $sortedByMMR->values()->search(function($p) use ($player) {
                    return $p['user_id'] === $player['user_id'];
                }) + 1;
            }
            $expectedPoints = $this->getPointsForPosition($expectedPosition, $playerCount);
            $pointDifference = ($basePoints - $expectedPoints) / 10;

            $kFactor = $this->getDynamicKFactor($gamesPlayed, $player['current_mmr'], $averageMMR);
            $rawChange = $kFactor * $pointDifference;
            $finalChange = round($rawChange);
            $mmrChanges[$player['user_id']] = [
                'change' => $finalChange,
                'position' => $position,
                'base_points' => $basePoints,
                'expected_points' => $expectedPoints,
                'point_difference' => $pointDifference,
                'k_factor' => $kFactor
            ];
        
        }
        
        return $mmrChanges;
    }
    private function assignFinishingPositions($players)
    {
        $winner = $players->firstWhere('is_winner', true);
        
        $eliminatedPlayers = $players->where('is_winner', false)
            ->whereNotNull('order_lost')
            ->sortBy('order_lost');
        
        $position = 2;
        $playersWithPosition = [];
        
        if ($winner) {
            $playersWithPosition[] = array_merge($winner, ['position' => 1]);
        }
        
        foreach ($eliminatedPlayers->reverse() as $player) {
            $playersWithPosition[] = array_merge($player, ['position' => $position]);
            $position++;
        }
        
        $remainingPlayers = $players->where('winner', false)
            ->whereNull('order_lost')
            ->values();
            
        foreach ($remainingPlayers as $player) {
            $playersWithPosition[] = array_merge($player, ['position' => $position]);
            $position++;
        }
        
        return collect($playersWithPosition);
    }

    private function getCurrentMMRForPlayers($players, $matchType)
    {
        $userIds = $players->pluck('user_id')->toArray();
        
        $recentMMR = \DB::table('match_participants')
            ->join('matches', 'match_participants.match_id', '=', 'matches.match_id')
            ->whereIn('match_participants.user_id', $userIds)
            ->where('matches.match_type', $matchType)
            ->whereNotNull('match_participants.mmr_after')
            ->select('match_participants.user_id', 'match_participants.mmr_after')
            ->orderBy('matches.played_at', 'DESC')
            ->get()
            ->groupBy('user_id')
            ->map(function($records) {
                return $records->first()->mmr_after;
            });
        
        return $players->map(function($player) use ($recentMMR) {
            $player['current_mmr'] = $recentMMR[$player['user_id']] ?? $this->getStartingMMR();
            return $player;
        });
    }
    private function getPointsForPosition($position, $playerCount)
    {
        switch($playerCount) {
            case 2:
                return self::BASE_POINTS_2[$position] ?? 0;
            case 3:
                return self::BASE_POINTS_3[$position] ?? 0;
            case 4:
                return self::BASE_POINTS_4[$position] ?? 0;
            case 5:
                return self::BASE_POINTS_5[$position] ?? 0;
            case 6:
                return self::BASE_POINTS_6[$position] ?? 0;
            default:
                return 0;
        }        
    }

    
    private function getDynamicKFactor($gamesPlayed, $playerMMR, $averageMMR)
    {
        
        
        if ($gamesPlayed < 10) return self::K_FACTOR_VOLATILE;
        if ($gamesPlayed < 20) return self::K_FACTOR_BASE * 1.2;
        
        $mmrDifference = $playerMMR - $averageMMR;
        $differenceFactor = 1.0;
        
        if (abs($mmrDifference) > 200) {
            $differenceFactor = 0.7;
        } elseif (abs($mmrDifference) > 100) {
            $differenceFactor = 0.85;
        }
        
        return self::K_FACTOR_BASE * $differenceFactor;
    }

    private function getGameSizeMultiplier($playerCount)
    {
        return [
            3 => 0.8, 
            4 => 1.0, 
            5 => 1.2, 
            6 => 1.4  
        ][$playerCount] ?? 1.0;
    }

    private function getGamesPlayed($userId, $matchType)
{
    return MatchParticipant::join('matches', 'match_participants.match_id', '=', 'matches.match_id')
        ->where('match_participants.user_id', $userId)
        ->where('matches.match_type', $matchType)
        ->count();
}

    public function getStartingMMR()
    {
        return 1200;
    }
}