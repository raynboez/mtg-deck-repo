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
            'date_played' => 'required|date',
            'game_mode' => ['required', 'string', Rule::enum(GameMode::class)],
            'players.*.user_id' => 'required|exists:users,user_id',
            'players.*.army_id' => 'required|exists:armies,army_id',
            'players.*.is_winner' => 'required|boolean',
            'players.*.victory_points' => 'required|integer',            
            'players.*.primary_points' => 'nullable|integer',
            'players.*.secondary_points' => 'nullable|integer',
            'players.*.tertiary_points' => 'nullable|integer',
            'players.*.primary_objective' => 'nullable|string',
            'players.*.secondary_objective' => 'nullable|string',
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
                'primary_points' => $playerData['primary_points'] ?? null,
                'secondary_points' => $playerData['secondary_points'] ?? null,
                'tertiary_points' => $playerData['tertiary_points'] ?? null,
                'primary_objective' => $playerData['primary_objective'] ?? null,
                'secondary_objective' => $playerData['secondary_objective'] ?? null,
            ]);
        }


    }

}
