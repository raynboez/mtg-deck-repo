<?php

namespace App\Http\Controllers;


use App\Models\Season;
use App\Models\User;
use App\Models\Deck;
use App\Models\Matches;
use App\Models\WarhammerMatch;
use App\Models\WarhammerMatchParticipant;
use App\Models\Army;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;


class WarhammerStatsController extends Controller
{
    public function index(Request $request)
    {

        $validated = $request->validate([
            'period' => 'sometimes|string|in:all,month,week',
            'format' => 'sometimes|string',
        ]);

        $period = $validated['period'] ?? 'all';
        $game_mode = $validated['format'] ?? 'all';

        $query = WarhammerMatch::with(['participants.user', 'participants.army']);
        if ($period !== 'all') {
            $query->where('played_at', '>=', $this->getDateFromPeriod($period));
        }

        if ($game_mode !== 'all') {
            $query->where('game_mode', $game_mode);
        }

        $matches = $query->orderBy('played_at')->get();

        $stats = $this->calculateStatistics($matches, $game_mode);
        return response()->json([
            'success' => true,
            'filters' => [
                'period' => $period,
                'game_mode' => $game_mode,
            ],
            'statistics' => $stats,
            'match_count' => $matches->count(),
        ]);
    }


private function calculateMatchups($matches): array
{
    if ($matches->isEmpty()) {
        return [];
    }

    $matchIds = $matches->pluck('match_id')->toArray();
    
    $allParticipants = DB::table('warhammer_match_participants')
        ->whereIn('match_id', $matchIds)
        ->orderBy('match_id')
        ->orderBy('user_id')
        ->get();
    
    Log::info('All participants:', ['count' => $allParticipants->count()]);
    
    $groupedByMatch = $allParticipants->groupBy('match_id');
    
    $matchupData = [];
    
    foreach ($groupedByMatch as $matchId => $participants) {
        $sorted = $participants->sortBy('user_id')->values();
        
        for ($i = 0; $i < $sorted->count(); $i += 2) {
            if ($i + 1 >= $sorted->count()) break;
            
            $p1 = $sorted[$i];
            $p2 = $sorted[$i + 1];
            
            $key = min($p1->user_id, $p2->user_id) . '_' . max($p1->user_id, $p2->user_id);
            
            if (!isset($matchupData[$key])) {
                $matchupData[$key] = [
                    'player1_id' => $p1->user_id,
                    'player1_name' => $this->getUserName($p1->user_id),
                    'player2_id' => $p2->user_id,
                    'player2_name' => $this->getUserName($p2->user_id),
                    'total_matches' => 0,
                    'player1_wins' => 0,
                    'player2_wins' => 0,
                    'player1_vp_sum' => 0,
                    'player2_vp_sum' => 0,
                    'player1_primary_sum' => 0,
                    'player2_primary_sum' => 0,
                    'last_played' => null,
                ];
            }
            
            $matchupData[$key]['total_matches']++;
            $matchupData[$key]['player1_vp_sum'] += $p1->victory_points ?? 0;
            $matchupData[$key]['player2_vp_sum'] += $p2->victory_points ?? 0;
            $matchupData[$key]['player1_primary_sum'] += $p1->primary_points ?? 0;
            $matchupData[$key]['player2_primary_sum'] += $p2->primary_points ?? 0;
            
            if ($p1->is_winner) {
                $matchupData[$key]['player1_wins']++;
            } else {
                $matchupData[$key]['player2_wins']++;
            }
            
            $match = $matches->firstWhere('match_id', $matchId);
            if ($match && (!$matchupData[$key]['last_played'] || $match->played_at > $matchupData[$key]['last_played'])) {
                $matchupData[$key]['last_played'] = $match->played_at;
            }
        }
    }
    
    return $this->formatMatchupData($matchupData);
}

private function getUserName($userId)
{
    $user = DB::table('users')->where('user_id', $userId)->first();
    return $user->name ?? 'Unknown';
}

private function formatMatchupData($matchupData): array
{
    $formattedMatchups = [];
    
    foreach ($matchupData as $data) {
        if ($data['total_matches'] === 0) continue;
        
        $p1_avg_vp = round($data['player1_vp_sum'] / $data['total_matches'], 1);
        $p2_avg_vp = round($data['player2_vp_sum'] / $data['total_matches'], 1);
        
        $formattedMatchups[] = [
            'player_id' => $data['player1_id'],
            'player_name' => $data['player1_name'],
            'opponent_id' => $data['player2_id'],
            'opponent_name' => $data['player2_name'],
            'wins' => $data['player1_wins'],
            'losses' => $data['player2_wins'],
            'total_matches' => $data['total_matches'],
            'win_rate' => $data['total_matches'] > 0 
                ? round(($data['player1_wins'] / $data['total_matches']) * 100, 1)
                : 0,
            'avg_victory_points' => $p1_avg_vp,
            'avg_opponent_points' => $p2_avg_vp,
            'last_played' => $data['last_played']?->format('Y-m-d') ?? null,
        ];
        
        $formattedMatchups[] = [
            'player_id' => $data['player2_id'],
            'player_name' => $data['player2_name'],
            'opponent_id' => $data['player1_id'],
            'opponent_name' => $data['player1_name'],
            'wins' => $data['player2_wins'],
            'losses' => $data['player1_wins'],
            'total_matches' => $data['total_matches'],
            'win_rate' => $data['total_matches'] > 0 
                ? round(($data['player2_wins'] / $data['total_matches']) * 100, 1)
                : 0,
            'avg_victory_points' => $p2_avg_vp,
            'avg_opponent_points' => $p1_avg_vp,
            'last_played' => $data['last_played']?->format('Y-m-d') ?? null,
        ];
    }
    
    usort($formattedMatchups, function($a, $b) {
        return $b['win_rate'] <=> $a['win_rate'];
    });
    
    return $formattedMatchups;
}
    private function getDateFromPeriod(string $period): Carbon
    {
        switch ($period) {
            case 'month':
                return Carbon::now()->subMonth();
            case 'week':
                return Carbon::now()->subWeek();
            default:
                return Carbon::createFromTimestamp(0);
        }
    }

    private function calculateStatistics($matches, $game_mode): array
    {
        $totalMatches = $matches->count();
        $matchups = $this->calculateMatchups($matches);
        $totalParticipants = 0;
        $playerStats = [];
        $FactionDistribution = [];
        $labels = [];
        $datasets = [];
        $season = Season::where('name', $game_mode)->first();
        if(isset($season)){
            $labels[] = $season->date_started->format('d-m-y H:i');
        } else {
            $labels[] = "00-00-00 00:00";
        }
        $seasonStart = $season ? $season->date_started : null;

        $mmrService = app(\App\Services\MMRService::class);
        $datapointPadding = (isset($season) && $season->id == 1 || !isset($season))
            ? 0
            : $mmrService->getStartingMMR($season->id);

        foreach ($matches as $match) {
    $labels[] = $match->played_at->format('d-m-y H:i');
    $totalParticipants += $match->participants->count();

    $winnerVP = null;
    $winnerId = null;
    foreach ($match->participants as $participant) {
        if ($participant->is_winner) {
            $winnerVP = $participant->victory_points;
            $winnerId = $participant->user_id;
            break;
        }
    }

    // FIRST PASS: Initialize player stats for ALL participants in this match
    foreach ($match->participants as $participant) {
        $userId = $participant->user_id;
        $userName = $participant->user->name ?? 'Unknown';

        if (!isset($playerStats[$userId])) {
            $playerStats[$userId] = [
                'user_id' => $userId,
                'name' => $userName,
                'wins' => 0,
                'losses' => 0,
                'total_games' => 0,
                'armies' => [],
                'most_victory_points' => 0,
                'total_victory_points' => 0,
                'total_primary_points' => 0,
                'total_secondary_points' => 0,
                'total_tertiary_points' => 0,
                'biggest_stomp' => 0, 
                'biggest_stomp_against' => null,
                'largest_delta' => 0,
                'points' => 0,
                'factions' => [
                    'Astartes' => 0,
                    'Chaos' => 0,
                    'Imperium' => 0,
                    'Xenos' => 0               
                ],
                'mmr_history' => [],
            ];
        }
    }

    foreach ($match->participants->sortByDesc('order_lost') as $participant) {
        $userId = $participant->user_id;
        $userName = $participant->user->name ?? 'Unknown';

        if ($seasonStart && $match->played_at >= $seasonStart) {
            if (!is_null($participant->mmr_after)) {
                $playerStats[$userId]['mmr_history'][] = $participant->mmr_after;
            }   
        }

        $playerStats[$userId]['total_games']++;
        $vp = $participant->victory_points;
        $playerStats[$userId]['total_victory_points'] += $vp;
        if($vp > $playerStats[$userId]['most_victory_points']){
            $playerStats[$userId]['most_victory_points'] = $vp;
        }
        $playerStats[$userId]['total_primary_points'] += $participant->primary_points;
        $playerStats[$userId]['total_secondary_points'] += $participant->secondary_points;
        $playerStats[$userId]['total_tertiary_points'] += $participant->tertiary_points;

        if (!$participant->is_winner && $winnerVP !== null && $winnerId !== null) {
            $delta = $winnerVP - $participant->victory_points;
            if ($delta > $playerStats[$userId]['largest_delta']) {
                $playerStats[$userId]['largest_delta'] = $delta;
            }
            
            if ($delta > $playerStats[$winnerId]['biggest_stomp']) {
                $playerStats[$winnerId]['biggest_stomp'] = $delta;
                $playerStats[$winnerId]['biggest_stomp_against'] = $userName;
                $playerStats[$winnerId]['biggest_stomp_match_id'] = $match->match_id;
                $playerStats[$winnerId]['biggest_stomp_date'] = $match->played_at;
            }
        }

        if ($participant->is_winner) {
            $playerStats[$userId]['wins']++;
        } else {
            $playerStats[$userId]['losses']++;
        }

        if (!isset($datasets[$userId])) {
            $datasets[$userId] = [
                'label' => $userName,
                'data' => [],
                'tension' => 0
            ];
            $datasets[$userId]['data'][] = $datapointPadding;
        }
        
        while(sizeof($datasets[$userId]['data']) < sizeof($labels) - 1){
            if(empty($datasets[$userId]['data'])){
                $datasets[$userId]['data'][] = $datapointPadding;
            } else {
                $lastVal = end($datasets[$userId]['data']);
                $datasets[$userId]['data'][] = $lastVal;
            }
        }
        if(isset($season) && $season->id == 1 || !isset($season)){
            $datasets[$userId]['data'][] = $playerStats[$userId]['points'];
        } else {
            $datasets[$userId]['data'][] = $participant->mmr_after;
        }
        if ($participant->army_id) {
            $armyId = $participant->army_id;
            $armyName = $participant->army->name ?? 'Unknown Army';
            $armySubfaction = $participant->army->subfaction ?? 'Unknown Subfaction';
            $faction = $participant->army->faction;
            $photo = $participant->army->primaryPhoto()->first()->photo_url ?? null;
            if (!isset($playerStats[$userId]['armies'][$armyId])) {
                $playerStats[$userId]['armies'][$armyId] = [
                    'count' => 0,
                    'name' => $armyName,
                    'subfaction' => $armySubfaction,
                    'faction' => $faction,
                    'photo_url' => $photo
                ];
                
                if(!isset($FactionDistribution[$armySubfaction])){
                    $FactionDistribution[$armySubfaction] = 0;
                }
                $FactionDistribution[$armySubfaction]++;
            }
            
            $playerStats[$userId]['armies'][$armyId]['count']++;
            
            $faction = $participant->army->faction;
            $playerStats[$userId]['factions'][$faction]++;                 
        }
    }
}

        foreach ($playerStats as &$player) {
            $player['win_rate'] = $player['total_games'] > 0 
                ? round(($player['wins'] / $player['total_games']) * 100, 1) 
                : 0;
            
            $player['avg_victory_points'] = $player['total_games'] > 0 
                ? round($player['total_victory_points'] / $player['total_games'], 1) 
                : 0;
            
            $player['avg_primary_points'] = $player['total_games'] > 0 
                ? round($player['total_primary_points'] / $player['total_games'], 1) 
                : 0;
                $player['avg_secondary_points'] = $player['total_games'] > 0 
                ? round($player['total_secondary_points'] / $player['total_games'], 1) 
                : 0;
                $player['avg_tertiary_points'] = $player['total_games'] > 0 
                ? round($player['total_tertiary_points'] / $player['total_games'], 1) 
                : 0;

            if (!empty($player['armies'])) {
                uasort($player['armies'], function($a, $b) {
                    return $b['count'] <=> $a['count'];
                });
                
                $favoriteArmyData = reset($player['armies']);
                
                            
                $player['favourite_army'] = $favoriteArmyData['name'];
                $player['favourite_army_subfaction'] = $favoriteArmyData['subfaction'];
                $player['photo_url'] = $favoriteArmyData['photo_url'];
            } else {
                $player['favourite_army'] = 'No armies played';
                $player['favourite_army_subfaction'] = null;
                $player['photo_url'] = null;
            }

            if (!empty($player['mmr_history'])) {
                $player['current_season_mmr'] = end($player['mmr_history']);
                $player['peak_season_mmr'] = max($player['mmr_history']);
                $player['lowest_season_mmr'] = min($player['mmr_history']);
            } else {
                $player['current_season_mmr'] = null;
                $player['peak_season_mmr'] = null;
                $player['lowest_season_mmr'] = null;
            }

            unset($player['total_starting_life'], $player['total_final_life'], $player['armies'], $player['mmr_history']);
        }

        return [
            'total_matches' => $totalMatches,
            'labels' => $labels,
            'datasets' => $datasets,
            'average_players_per_game' => $totalMatches > 0 ? round($totalParticipants / $totalMatches, 1) : 0,
            'player_stats' => array_values($playerStats),
            'faction_distribution' => $FactionDistribution,
            'recent_matches' => $matches->map(function ($match) {
                return [
                    'id' => $match->match_id,
                    'date' => $match->played_at->format('Y-m-d'),
                    'game_mode' => $match->game_mode,
                    'players' => $match->participants->toArray(),
                    'winner' => $match->participants->where('is_winner', true)->first()->user->name ?? 'Unknown',
                ];
            }),
            'matchups' => $matchups,
        ];
    }


    public function playerStats(Request $request, $playerId)
    {
        $validated = $request->validate([
            'period' => 'sometimes|string|in:all,month,week',
            'game_mode' => 'sometimes|string',
        ]);

        $period = $validated['period'] ?? 'all';
        $game_mode = $validated['game_mode'] ?? 'all';

        $participantQuery = WarhammerMatchParticipant::with(['match', 'user', 'army'])
            ->where('user_id', $playerId);

        $participantQuery->whereHas('match', function ($query) use ($period, $game_mode) {
            if ($period !== 'all') {
                $query->where('played_at', '>=', $this->getDateFromPeriod($period));
            }
            if ($game_mode !== 'all') {
                $query->where('game_mode', $game_mode);
            }
        });

        $participations = $participantQuery->get();

        $stats = $this->calculatePlayerStatistics($participations);

        return response()->json([
            'success' => true,
            'player_id' => $playerId,
            'filters' => compact('period', 'game_mode'),
            'statistics' => $stats,
        ]);
    }

    private function calculatePlayerStatistics($participations): array
    {
        if ($participations->isEmpty()) {
            return [
                'total_games' => 0,
                'wins' => 0,
                'losses' => 0,
                'win_rate' => 0,
                'avg_victory_points_wh' => 0,
                'avg_victory_points_kt' => 0,
                'favourite_army' => 'No armies played',
                'armies' => [],
            ];
        }

        $stats = [
            'total_games' => $participations->count(),
            'wins' => $participations->where('is_winner', true)->count(),
            'losses' => $participations->where('is_winner', false)->count(),
            'total_starting_life' => $participations->where('game_mode', "Warhammer 40k")->sum('victory_points'),
            'deck_usage' => [],
        ];

        $stats['win_rate'] = round(($stats['wins'] / $stats['total_games']) * 100, 1);
        $stats['avg_starting_life'] = round($stats['total_starting_life'] / $stats['total_games'], 1);
        $stats['avg_final_life'] = round($stats['total_final_life'] / $stats['total_games'], 1);

        foreach ($participations as $participation) {
            if ($participation->deck_id) {
                $deckName = $participation->deck->deck_name ?? 'Unknown Deck';
                if (!isset($stats['deck_usage'][$deckName])) {
                    $stats['deck_usage'][$deckName] = 0;
                }
                $stats['deck_usage'][$deckName]++;
            }
        }

        if (!empty($stats['deck_usage'])) {
            arsort($stats['deck_usage']);
            $stats['favourite_deck'] = array_key_first($stats['deck_usage']);
        } else {
            $stats['favourite_deck'] = 'No decks played';
        }

        unset($stats['total_starting_life'], $stats['total_final_life']);

        return $stats;
    }
}