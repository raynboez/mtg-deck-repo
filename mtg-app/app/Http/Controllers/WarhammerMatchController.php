<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Enums\GameMode;
use Illuminate\Validation\Rule;
use App\Models\WarhammerMatch;
use App\Models\WarhammerMatchParticipant;
class WarhammerMatchController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'players' => 'required|array',
            'players.*.user_id' => 'required|exists:users,user_id',
            'players.*.army_id' => 'required|exists:army,army_id',
            'players.*.is_winner' => 'required|boolean',
            'players.*.victory_points' => 'required|integer',            
            'players.*.primary_score' => 'nullable|integer',
            'players.*.secondary_score' => 'nullable|integer',
            'players.*.tertiary_score' => 'nullable|integer',
            'date_played' => 'required|date',
            'game_mode' => 'required|string' .  Rule::enum(GameMode::class)
        ]);

        $match = WarhammerMatch::create([
            'played_at' => $validated['date_played'],
            'game_mode' => GameMode::from($validated['game_mode']),
            'number_of_players' => count($validated['players'])
        ]);

        foreach($validated['players'] as $playerData) {
            WarhammerMatchParticipant::create([
                'match_id' => $match->match_id,
                'user_id' => $playerData['user_id'],
                'army_id' => $playerData['army_id'],
                'is_winner' => $playerData['is_winner'],
                'victory_points' => $playerData['victory_points'],
                'primary_score' => $playerData['primary_score'] ?? null,
                'secondary_score' => $playerData['secondary_score'] ?? null,
                'tertiary_score' => $playerData['tertiary_score'] ?? null
            ]);
        }


    }

}
