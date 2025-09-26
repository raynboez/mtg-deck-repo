<?php
namespace App\Services;

use function GuzzleHttp\default_ca_bundle;

use Illuminate\Support\Facades\Log;

class MMRService
{
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
    
    const POSITION_BONUS = 5;
    const POSITION_PENALTY = 3;

    public function calculateMatchMMR(array $playersData, $format)
    {
        $players = collect($playersData);
        Log::info("". $players);
        $playerCount = $players->count();

        $playersWithPosition = $this->assignFinishingPositions($players);
        Log::info("" . $playersWithPosition);
        $playersWithMMR = $this->getCurrentMMRForPlayers($playersWithPosition, $format);
        Log::info("" . $playersWithMMR);
        $sortedByMMR = $playersWithMMR->sortByDesc('current_mmr');
        Log::info("" . $sortedByMMR);
        $mmrChanges = [];
        
        foreach ($playersWithPosition as $player) {
            $position = $player['position'];
            $basePoints = $this->getPointsForPosition($position, $playerCount);
            
            $expectedPosition = $sortedByMMR->values()->search(function($p) use ($player) {
                return $p['user_id'] === $player['user_id'];
            }) + 1;
            
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
                return self::BASE_POINTS_3[$position] ?? 0;
            case 5:
                return self::BASE_POINTS_3[$position] ?? 0;
            case 6:
                return self::BASE_POINTS_3[$position] ?? 0;
            default:
                return 0;
        }        
    }

    public function getStartingMMR()
    {
        return 1200;
    }
}