<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Deck;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function userStats($userId)
    {
        $stats = DB::table('match_participants')
            ->select(
                DB::raw('COUNT(*) as total_matches'),
                DB::raw('SUM(CASE WHEN is_winner = 1 THEN 1 ELSE 0 END) as wins'),
                DB::raw('SUM(CASE WHEN is_winner = 0 THEN 1 ELSE 0 END) as losses')
            )
            ->where('user_id', $userId)
            ->first();

        return response()->json([
            'total_matches' => $stats->total_matches,
            'wins' => $stats->wins,
            'losses' => $stats->losses,
            'win_rate' => $stats->total_matches > 0 
                ? round(($stats->wins / $stats->total_matches) * 100, 2) 
                : 0
        ]);
    }

    public function deckStats($deckId)
    {
        $stats = DB::table('match_participants')
            ->select(
                DB::raw('COUNT(*) as total_matches'),
                DB::raw('SUM(CASE WHEN is_winner = 1 THEN 1 ELSE 0 END) as wins'),
                DB::raw('SUM(CASE WHEN is_winner = 0 THEN 1 ELSE 0 END) as losses')
            )
            ->where('deck_id', $deckId)
            ->first();

        return response()->json([
            'total_matches' => $stats->total_matches,
            'wins' => $stats->wins,
            'losses' => $stats->losses,
            'win_rate' => $stats->total_matches > 0 
                ? round(($stats->wins / $stats->total_matches) * 100, 2) 
                : 0
        ]);
    }
}