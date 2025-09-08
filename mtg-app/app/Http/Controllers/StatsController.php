<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Models\Deck;
use App\Models\Matches;
use App\Models\MatchParticipant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Log;

class StatsController extends Controller
{
    public function index(Request $request)
    {

        $validated = $request->validate([
            'period' => 'sometimes|string|in:all,month,week',
            'format' => 'sometimes|string|in:all,Gulag Commander - Season 0,Casual Commander,Custom Game',
            'bracket' => 'sometimes|integer|min:1|max:5',
        ]);

        $period = $validated['period'] ?? 'all';
        $format = $validated['format'] ?? 'all';
        $bracket = $validated['bracket'] ?? 'all';

        $query = Matches::with(['participants.user', 'participants.deck']);
        if ($period !== 'all') {
            $query->where('played_at', '>=', $this->getDateFromPeriod($period));
        }

        if ($format !== 'all') {
            $query->where('match_type', $format);
        }

        if ($bracket !== 'all') {
            $query->where('bracket', $bracket);
        }

        $matches = $query->get();

        $stats = $this->calculateStatistics($matches);

        return response()->json([
            'success' => true,
            'filters' => [
                'period' => $period,
                'format' => $format,
                'bracket' => $bracket,
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

    private function calculateStatistics($matches): array
    {
        $totalMatches = $matches->count();
        
        $totalParticipants = 0;
        $totalTurns = 0;
        $playerStats = [];
        $bracketStats = [];
        $colourDistribution = [];

        foreach ($matches as $match) {
            $totalParticipants += $match->participants->count();
            $totalTurns+=$match->total_turns;
            if (isset($match->bracket)) {
                $bracket = $match->bracket;
                if (!isset($bracketStats[$bracket])) {
                    $bracketStats[$bracket] = 0;
                }
                $bracketStats[$bracket]++;
            }

            foreach ($match->participants as $participant) {
                $userId = $participant->user_id;
                Log::info($participant->user);
                $userName = $participant->user->name ?? 'Unknown';

                if (!isset($playerStats[$userId])) {
                    $playerStats[$userId] = [
                        'user_id' => $userId,
                        'name' => $userName,
                        'wins' => 0,
                        'losses' => 0,
                        'total_games' => 0,
                        'decks' => [],
                        'total_starting_life' => 0,
                        'total_final_life' => 0,
                    ];
                }

                $playerStats[$userId]['total_games']++;
                $playerStats[$userId]['total_starting_life'] += $participant->starting_life;
                $playerStats[$userId]['total_final_life'] += $participant->final_life;

                if ($participant->is_winner) {
                    $playerStats[$userId]['wins']++;
                } else {
                    $playerStats[$userId]['losses']++;
                }

                if ($participant->deck_id) {
                    $deckName = $participant->deck->deck_name ?? 'Unknown Deck';
                    if (!isset($playerStats[$userId]['decks'][$deckName])) {
                        $playerStats[$userId]['decks'][$deckName] = 0;
                        $colour = $participant->deck->colour_identity;
                        if(!isset($colourDistribution[$colour])){
                        $colourDistribution[$colour] = 0;
                    }
                    $colourDistribution[$colour]++;
                    }
                    $playerStats[$userId]['decks'][$deckName]++;
                    
                    
                }
            }
        }

        foreach ($playerStats as &$player) {
            $player['win_rate'] = $player['total_games'] > 0 
                ? round(($player['wins'] / $player['total_games']) * 100, 1) 
                : 0;
            
            $player['avg_starting_life'] = $player['total_games'] > 0 
                ? round($player['total_starting_life'] / $player['total_games'], 1) 
                : 0;
            
            $player['avg_final_life'] = $player['total_games'] > 0 
                ? round($player['total_final_life'] / $player['total_games'], 1) 
                : 0;

            if (!empty($player['decks'])) {
                arsort($player['decks']);
                $player['favorite_deck'] = array_key_first($player['decks']);
            } else {
                $player['favorite_deck'] = 'No decks played';
            }

            unset($player['total_starting_life'], $player['total_final_life'], $player['decks']);
        }

        return [
            'total_matches' => $totalMatches,
            'average_players_per_game' => $totalMatches > 0 ? round($totalParticipants / $totalMatches, 1) : 0,
            'average_turns_per_game' => $totalMatches > 0 ? round($totalTurns / $totalMatches, 1) : 0,
            'player_stats' => array_values($playerStats),
            'colour_distribution' => $colourDistribution,
            'bracket_distribution' => $bracketStats,
            'recent_matches' => $matches->take(10)->map(function ($match) {
                return [
                    'id' => $match->match_id,
                    'date' => $match->played_at->format('Y-m-d'),
                    'format' => $match->match_type,
                    'totalTurns' => $match->total_turns,
                    'bracket' => $match->bracket ?? null,
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
            'format' => 'sometimes|string|in:all,Gulag Commander - Season 0,Casual Commander,Custom Game',
            'bracket' => 'sometimes|integer|min:1|max:5',
        ]);

        $period = $validated['period'] ?? 'all';
        $format = $validated['format'] ?? 'all';
        $bracket = $validated['bracket'] ?? 'all';

        $participantQuery = MatchParticipant::with(['match', 'user', 'deck'])
            ->where('user_id', $playerId);

        $participantQuery->whereHas('match', function ($query) use ($period, $format, $bracket) {
            if ($period !== 'all') {
                $query->where('played_at', '>=', $this->getDateFromPeriod($period));
            }
            if ($format !== 'all') {
                $query->where('format', $format);
            }
            if ($bracket !== 'all') {
                $query->where('bracket', $bracket);
            }
        });

        $participations = $participantQuery->get();

        $stats = $this->calculatePlayerStatistics($participations);

        return response()->json([
            'success' => true,
            'player_id' => $playerId,
            'filters' => compact('period', 'format', 'bracket'),
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
                'avg_starting_life' => 0,
                'avg_final_life' => 0,
                'favorite_deck' => 'No decks played',
                'deck_usage' => [],
            ];
        }

        $stats = [
            'total_games' => $participations->count(),
            'wins' => $participations->where('is_winner', true)->count(),
            'losses' => $participations->where('is_winner', false)->count(),
            'total_starting_life' => $participations->sum('starting_life'),
            'total_final_life' => $participations->sum('final_life'),
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
            $stats['favorite_deck'] = array_key_first($stats['deck_usage']);
        } else {
            $stats['favorite_deck'] = 'No decks played';
        }

        unset($stats['total_starting_life'], $stats['total_final_life']);

        return $stats;
    }
}