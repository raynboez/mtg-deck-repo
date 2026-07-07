<?php
namespace App\Services;

use App\Models\MatchParticipant;
use Illuminate\Support\Facades\Log;
use App\Models\Season;
class MMRService
{

    const K_FACTOR_BASE = 25;
    const K_FACTOR_VOLATILE = 40;
    const firstBloodBonus = 5;
    const motmBonus = 10;
    const jotmBonus = -10;

    public function calculateMatchMMR(array $playersData, $format)
    {
        $players = collect($playersData);
        $playerCount = $players->count();
        $seasonId = Season::where('name', $format)->value('id');
        $playersWithPosition = $this->assignFinishingPositions($players);
        $playersWithMMR = $this->getCurrentMMRForPlayers($playersWithPosition, $format, $seasonId);

        $mmrChanges = [];
        $averageMMR = $playersWithMMR->avg('current_mmr');

        $actualScores = [];
        $sorted = $playersWithMMR->sortBy('position')->values();
        foreach ($sorted as $i => $player) {
            $actualScores[$player['user_id']] = ($playerCount - $player['position']) / ($playerCount - 1);
        }

        $firstBloodBonus = 3;
        $motmBonus = 5;
        $jotmBonus = -5;

        foreach ($playersWithMMR as $player) {
            $playerMMR = $player['current_mmr'];
            $expectedScore = 0;
            foreach ($playersWithMMR as $opponent) {
                if ($opponent['user_id'] === $player['user_id']) continue;
                $expectedScore += 1 / (1 + pow(10, (($opponent['current_mmr'] - $playerMMR) / 400)));
            }
            $expectedScore /= ($playerCount - 1);

            $actualScore = $actualScores[$player['user_id']];
            $gamesPlayed = $this->getGamesPlayed($player['user_id'], $format);
            $kFactor = $this->getDynamicKFactor($gamesPlayed, $playerMMR, $averageMMR);

            $change = round($kFactor * ($actualScore - $expectedScore));


            if ($change !== 0 && abs($change) < 7) {
                $change = $change > 0 ? 7 : -7;
            }

            $bonus = 0;
            if (!empty($player['first_blood'])) {
                $bonus += $firstBloodBonus;
            }
            if (!empty($player['motm'])) {
                $bonus += $motmBonus;
            }
            if (!empty($player['jotm'])) {
                $bonus += $jotmBonus;
            }
            $change += $bonus;
            Log::info("Player {$player['user_id']} - Pos: {$player['position']}, MMR: {$playerMMR}, Act: {$actualScore}, Exp: {$expectedScore}, K: {$kFactor}, Change: {$change} (Bonus: {$bonus})");
            $mmrChanges[$player['user_id']] = [
                'change' => $change,
                'position' => $player['position'],
                'actual_score' => $actualScore,
                'expected_score' => $expectedScore,
                'k_factor' => $kFactor,
                'bonus' => $bonus
            ];
        }

        return $mmrChanges;
    }

    private function assignFinishingPositions($players)
    {
        $winner = $players->firstWhere('is_winner', true);

        $eliminatedPlayers = $players->where('is_winner', false)
            ->whereNotNull('order_lost')
            ->sortByDesc('order_lost')
            ->values();

        $playersWithPosition = [];
        $position = 2;

        if ($winner) {
            $playersWithPosition[] = array_merge($winner, ['position' => 1]);
        }

        $i = 0;
        while ($i < $eliminatedPlayers->count()) {
            $currentOrderLost = $eliminatedPlayers[$i]['order_lost'];
            $drawGroup = $eliminatedPlayers->where('order_lost', $currentOrderLost)->values();
            $groupSize = $drawGroup->count();
            $averagePosition = $position + ($groupSize - 1) / 2;
            foreach ($drawGroup as $player) {
                $playersWithPosition[] = array_merge($player, ['position' => $averagePosition]);
            }
            $i += $groupSize;
            $position += $groupSize;
        }

        $remainingPlayers = $players->where('is_winner', false)
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
        $seasonId = Season::where('name', $matchType)->value('id');
        return $players->map(function($player) use ($recentMMR, $seasonId) {
            $player['current_mmr'] = $recentMMR[$player['user_id']] ?? $this->getStartingMMR($seasonId);
            return $player;
        });
    }

    private function getDynamicKFactor($gamesPlayed, $playerMMR, $averageMMR)
    {
        $baseK = self::K_FACTOR_BASE;
        $volatileK = self::K_FACTOR_VOLATILE;

        $kFactor = ($gamesPlayed < 10) ? $volatileK : $baseK;

        $mmrDiff = $playerMMR - $averageMMR;
        $scaling = 1.0;
        if ($mmrDiff > 0) {
            $scaling = max(0.7, 1.0 - min($mmrDiff, 400) / 1000);
        } elseif ($mmrDiff < 0) {
            $scaling = min(1.3, 1.0 - max($mmrDiff, -400) / 1000);
        }

        return $kFactor * $scaling;
    }

    private function getGamesPlayed($userId, $matchType)
    {
        return MatchParticipant::join('matches', 'match_participants.match_id', '=', 'matches.match_id')
            ->where('match_participants.user_id', $userId)
            ->where('matches.match_type', $matchType)
            ->count();
    }

    public function getStartingMMR(int $seasonId)
    {
        if($seasonId === 3){
            return 100;
        }
        return 500;
    }
}