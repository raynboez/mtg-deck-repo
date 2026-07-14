<?php
namespace App\Services;

use App\Models\MatchParticipant;
use App\Models\WarhammerMatchParticipant;
use Illuminate\Support\Facades\Log;
use App\Models\Season;
use Illuminate\Support\Facades\DB;
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
        $playersWithMMR = $this->getCurrentMMRForPlayers($playersWithPosition, $format);

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
    private const K_FACTOR = 32;
    private const INITIAL_RATING = 1200;

    /**
     * Get starting MMR for a season
     */
    public function getStartingMMR(int $seasonId): int
    {
        if ($seasonId === 3) {
            return 100;
        }
        return 500;
    }

    /**
     * Calculate MMR changes using chess ELO system
     */
    public function calculateWarhammerMatchMMR(array $playersData, $format): array
    {
        $players = collect($playersData);
        $playerCount = $players->count();
        
        // Get current MMR for all players
        $playersWithMMR = $this->getCurrentWarhammerMMRForPlayers($players, $format);
        
        // Get the winner (player with is_winner = true)
        $winner = $playersWithMMR->firstWhere('is_winner', true);
        
        if (!$winner) {
            Log::warning('No winner found in match');
            return [];
        }

        $mmrChanges = [];
        $winnerMMR = $winner['current_mmr'];

        // Calculate changes for losers
        foreach ($playersWithMMR as $player) {
            // Skip the winner
            if ($player['is_winner']) {
                continue;
            }

            $playerMMR = $player['current_mmr'];
            
            // Calculate expected score (probability of winning against the winner)
            $expectedScore = $this->calculateExpectedScore($playerMMR, $winnerMMR);
            
            // Actual score: 0 for loss
            $actualScore = 0;
            
            // Get dynamic K factor
            $gamesPlayed = $this->getWarhammerGamesPlayed($player['user_id'], $format);
            $kFactor = $this->getWarhammerDynamicKFactor($gamesPlayed, $playerMMR);
            
            // Calculate rating change
            $change = round($kFactor * ($actualScore - $expectedScore));
            
            // Ensure minimum change if significant
            if ($change !== 0 && abs($change) < 7) {
                $change = $change > 0 ? 7 : -7;
            }

            Log::info("Loser {$player['user_id']} - MMR: {$playerMMR}, Change: {$change}");
            
            $mmrChanges[$player['user_id']] = [
                'mmr_before' => $playerMMR,
                'mmr_after' => $playerMMR + $change,
                'mmr_change' => $change,
                'is_winner' => false,
                'actual_score' => $actualScore,
                'expected_score' => $expectedScore,
                'k_factor' => $kFactor,
                'winner_mmr' => $winnerMMR
            ];
        }

        // Calculate winner's change (sum of all loser changes, but positive)
        $totalLoserChange = collect($mmrChanges)->sum('mmr_change'); // FIXED: use mmr_change instead of change
        $winnerChange = abs($totalLoserChange); // Winner gains the total points lost by losers
        
        // Cap winner's gain to prevent excessive inflation
        $maxWinnerGain = $this->getMaxWinnerGain($winner['current_mmr']);
        if ($winnerChange > $maxWinnerGain) {
            $winnerChange = $maxWinnerGain;
        }

        // FIXED: Use $winnerChange, not undefined $change
        $mmrChanges[$winner['user_id']] = [
            'mmr_before' => $winnerMMR,
            'mmr_after' => $winnerMMR + $winnerChange,  // FIXED: Use $winnerChange
            'mmr_change' => $winnerChange,              // FIXED: Use $winnerChange
            'is_winner' => true,
            'actual_score' => 1.0,
            'expected_score' => $this->calculateExpectedScore($winnerMMR, $winnerMMR),
            'k_factor' => $this->getWarhammerDynamicKFactor(
                $this->getWarhammerGamesPlayed($winner['user_id'], $format),
                $winnerMMR
            ),
            'winner_mmr' => $winnerMMR
        ];

        // Balance the total change to zero
        $totalChange = collect($mmrChanges)->sum('mmr_change'); // FIXED: use mmr_change instead of change
        if ($totalChange != 0) {
            $adjustedWinnerChange = $winnerChange - $totalChange;
            $mmrChanges[$winner['user_id']]['mmr_change'] = round($adjustedWinnerChange);
            $mmrChanges[$winner['user_id']]['mmr_after'] = $winnerMMR + round($adjustedWinnerChange);
        }

        Log::info('MMR Changes:', $mmrChanges);
        
        return $mmrChanges;
    }

    private function calculateExpectedScore(int $playerRating, int $opponentRating): float
    {
        return 1 / (1 + pow(10, ($opponentRating - $playerRating) / 400));
    }

    private function getWarhammerDynamicKFactor(int $gamesPlayed, int $currentRating): int
    {
        if ($gamesPlayed < 30) {
            return 150;
        } elseif ($currentRating < 2100) {
            return 100;
        } elseif ($currentRating < 2400) {
            return 50;
        } else {
            return 25;
        }
    }

    private function getMaxWinnerGain(int $currentRating): int
    {
        if ($currentRating < 2100) {
            return 100;
        } elseif ($currentRating < 2400) {
            return 50;
        } else {
            return 25;
        }
    }

    private function getCurrentWarhammerMMRForPlayers($players, $matchType): \Illuminate\Support\Collection
    {
        $userIds = $players->pluck('user_id')->toArray();

        $recentMMR = DB::table('warhammer_match_participants')
            ->join('warhammer_matches', 'warhammer_match_participants.match_id', '=', 'warhammer_matches.match_id')
            ->whereIn('warhammer_match_participants.user_id', $userIds)
            ->where('warhammer_matches.game_mode', $matchType)
            ->whereNotNull('warhammer_match_participants.mmr_after')
            ->select('warhammer_match_participants.user_id', 'warhammer_match_participants.mmr_after')
            ->orderBy('warhammer_matches.played_at', 'DESC')
            ->get()
            ->groupBy('user_id')
            ->map(function ($records) {
                return $records->first()->mmr_after;
            });

        $seasonId = Season::where('name', $matchType)->value('id');

        return $players->map(function ($player) use ($recentMMR, $seasonId) {
            $player['current_mmr'] = $recentMMR[$player['user_id']] ?? $this->getStartingMMR($seasonId);
            return $player;
        });
    }

    /**
     * Get number of games played by a player in a format
     */
    private function getWarhammerGamesPlayed($userId, $matchType): int
    {
        return WarhammerMatchParticipant::join('warhammer_matches', 'warhammer_match_participants.match_id', '=', 'warhammer_matches.match_id')
            ->where('warhammer_match_participants.user_id', $userId)
            ->where('warhammer_matches.game_mode', $matchType)
            ->count();
    }

    /**
     * Get player rating history
     */
    public function getPlayerRatingHistory($userId, $format): array
    {
        $participants = WarhammerMatchParticipant::join('warhammer_matches', 'warhammer_match_participants.match_id', '=', 'warhammer_matches.match_id')
            ->where('warhammer_match_participants.user_id', $userId)
            ->where('warhammer_matches.game_mode', $format)
            ->whereNotNull('warhammer_match_participants.mmr_after')
            ->orderBy('warhammer_matches.played_at', 'asc')
            ->select('warhammer_match_participants.*', 'warhammer_matches.played_at')
            ->get();

        $history = [];
        foreach ($participants as $participant) {
            $history[] = [
                'date' => $participant->played_at->format('Y-m-d'),
                'rating' => $participant->mmr_after,
                'change' => $participant->mmr_after - $participant->mmr_before,
                'match_id' => $participant->match_id,
                'is_winner' => $participant->is_winner
            ];
        }

        return $history;
    }

    /**
     * Get player statistics
     */
    public function getPlayerStats($userId, $format): array
    {
        $participants = WarhammerMatchParticipant::join('warhammer_matches', 'warhammer_match_participants.match_id', '=', 'warhammer_matches.match_id')
            ->where('warhammer_match_participants.user_id', $userId)
            ->where('warhammer_matches.game_mode', $format)
            ->get();

        $totalGames = $participants->count();
        $wins = $participants->where('is_winner', true)->count();
        $losses = $totalGames - $wins;

        $currentMMR = $participants->sortByDesc('warhammer_matches.played_at')
            ->first()
            ->mmr_after ?? $this->getStartingMMR(
                Season::where('name', $format)->value('id')
            );

        return [
            'total_games' => $totalGames,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $totalGames > 0 ? round(($wins / $totalGames) * 100, 2) : 0,
            'current_mmr' => $currentMMR,
            'highest_mmr' => $participants->max('mmr_after') ?? $currentMMR
        ];
    }

    /**
     * Alternative implementation: Multiple winner support (for team games)
     */
    public function calculateWarhammerMatchMMRWithMultipleWinners(array $playersData, $format): array
    {
        $players = collect($playersData);
        $winners = $players->where('is_winner', true);
        $losers = $players->where('is_winner', false);
        
        if ($winners->isEmpty()) {
            Log::warning('No winners found in match');
            return [];
        }

        $playersWithMMR = $this->getCurrentWarhammerMMRForPlayers($players, $format);
        $mmrChanges = [];

        // Calculate average MMR of winners
        $winnerMMRs = $playersWithMMR->where('is_winner', true)->pluck('current_mmr');
        $averageWinnerMMR = $winnerMMRs->avg();

        // Calculate changes for losers (lose points to winners)
        foreach ($losers as $loser) {
            $loserData = $playersWithMMR->firstWhere('user_id', $loser['user_id']);
            $playerMMR = $loserData['current_mmr'];
            
            // Expected score against the average winner
            $expectedScore = $this->calculateExpectedScore($playerMMR, (int)$averageWinnerMMR);
            $actualScore = 0; // Loss
            
            $gamesPlayed = $this->getWarhammerGamesPlayed($loser['user_id'], $format);
            $kFactor = $this->getWarhammerDynamicKFactor($gamesPlayed, $playerMMR);
            
            $change = round($kFactor * ($actualScore - $expectedScore));
            
            if ($change !== 0 && abs($change) < 7) {
                $change = $change > 0 ? 7 : -7;
            }

            $mmrChanges[$loser['user_id']] = [
                'change' => $change,
                'is_winner' => false,
                'actual_score' => $actualScore,
                'expected_score' => $expectedScore,
                'k_factor' => $kFactor
            ];
        }

        // Distribute lost points among winners
        $totalLost = abs(collect($mmrChanges)->sum('change'));
        $winnersCount = $winners->count();
        
        foreach ($winners as $winner) {
            $winnerData = $playersWithMMR->firstWhere('user_id', $winner['user_id']);
            $winnerMMR = $winnerData['current_mmr'];
            
            // Each winner gets proportional share based on their rating
            $share = $winnerMMR / $winnerMMRs->sum();
            $change = round($totalLost * $share);
            
            // Ensure minimum gain
            if ($change < 7 && $change > 0) {
                $change = 7;
            }

            $mmrChanges[$winner['user_id']] = [
                'change' => $change,
                'is_winner' => true,
                'actual_score' => 1.0,
                'expected_score' => $this->calculateExpectedScore($winnerMMR, (int)$averageWinnerMMR),
                'k_factor' => $this->getWarhammerDynamicKFactor(
                    $this->getWarhammerGamesPlayed($winner['user_id'], $format),
                    $winnerMMR
                )
            ];
        }

        return $mmrChanges;
    }
}