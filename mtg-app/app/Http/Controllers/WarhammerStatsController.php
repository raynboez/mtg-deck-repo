<?php

namespace App\Http\Controllers;


use App\Models\Season;
use App\Models\User;
use App\Models\Deck;
use App\Models\Matches;
use App\Models\WarhammerMatch;
use App\Models\MatchParticipant;
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
            'game_mode' => 'sometimes|string',
        ]);

        $period = $validated['period'] ?? 'all';
        $game_mode = $validated['game_mode'] ?? 'all';

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
        
        $totalParticipants = 0;
        $totalTurns = 0;
        $playerStats = [];
        $bracketStats = [];
        $colourDistribution = [];
        $labels = [];
        $datasets = [];
        $season = Season::where('name', $game_mode)->first();
        if(isset($season)){
            $labels[] = $season->date_started->game_mode('d-m-y H:i');
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


            foreach ($match->participants->sortByDesc('order_lost') as $participant) {
                
                               
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


                if (!$participant->is_winner && $winnerVP !== null) {
                    $delta = $winnerVP - $participant->victory_points;
                    // Update largest delta (biggest stomp) for this player
                    if ($delta > $playerStats[$userId]['largest_delta']) {
                        $playerStats[$userId]['largest_delta'] = $delta;
                    }
                    
                    // Also track from winner's perspective
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
                    if (!isset($playerStats[$userId]['armies'][$armyId])) {
                        $playerStats[$userId]['armies'][$armyId] = [
                            'count' => 0,
                            'name' => $armyName,
                            'subfaction' => $armySubfaction,
                            'faction' => $faction
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
                $player['favourite_army_subfaction'] = $favoriteArmyData['subfaction'];;
            } else {
                $player['favourite_army'] = 'No armies played';
                $player['favourite_army_subfaction'] = null;
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