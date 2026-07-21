<?php

namespace App\Http\Controllers;

use DivisionByZeroError;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Army;
use Inertia\Inertia;
class ArmyController extends Controller
{
    public function userArmies(Request $request)
    {
        $user = $request->user()->user_id;
        $armies = DB::table('armies')->where('user_id', $user)->get();
        return response()->json($armies);
    }

    public function userArmiesById(Request $request, $userId)
    {
        $armies = DB::table('armies')->where('user_id', $userId)->get();
        return response()->json($armies);
    }

    public function getUsers(Request $request)
    {
        $users = DB::table('users')->select('user_id', 'name')->get();
        return response()->json($users);
    }

    public function show($id)
    {
        $army = Army::findOrFail($id);
        $id = $army->army_id;
        return Inertia::render('Army', [
            'army_id' => $id
        ]);
    }


public function updateArmy(Request $request, $armyId)
{
    $army = Army::findOrFail($armyId);

    if ($army->user_id !== auth()->id()) {
        return response()->json(['message' => 'Unauthorized Action'], 403);
    }
 
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'list' => 'nullable|string',
        'army_link' => 'nullable|url|max:255',
    ]);
    $updateData = [
        'name' => $validated['name'],
        'description' => $validated['description'],
        'army_link' => $validated['army_link'],
        'army_list' => $validated['list'] ?? null,
    ];

    $army->update($updateData);

    return response()->json([
        'message' => 'Army updated successfully!',
        'army' => $army
    ]);
}


public function getArmy(Request $request, $id)
{
    $user = $request->user()->user_id;
    $army = Army::findOrFail($id);
    
    $personalwins = DB::table('warhammer_match_participants')
        ->where('user_id', $user)
        ->where('army_id', $id)
        ->where('is_winner', 1)
        ->count();
    
    $personalgames = DB::table('warhammer_match_participants')
        ->where('user_id', $user)
        ->where('army_id', $id)
        ->count();
    
    $personalloss = $personalgames - $personalwins;
    
    try {
        $personalpercent = number_format(($personalwins / $personalgames) * 100);
    } catch (DivisionByZeroError) {
        $personalpercent = 0;
    }
    
    $wins = DB::table('warhammer_match_participants')
        ->where('army_id', $id)
        ->where('is_winner', 1)
        ->count();
    
    $games = DB::table('warhammer_match_participants')
        ->where('army_id', $id)
        ->count();
    
    $loss = $games - $wins;
    
    try {
        $percent = number_format(($wins / $games) * 100);
    } catch (DivisionByZeroError) {
        $percent = 0;
    }

    return response()->json([
        'army' => $army,
        'stats' => [
            'global' => [
                'wins' => $wins,
                'losses' => $loss,
                'total_games' => $games,
                'win_percentage' => (int) $percent,
            ],
            'personal' => [
                'wins' => $personalwins,
                'losses' => $personalloss,
                'total_games' => $personalgames,
                'win_percentage' => (int) $personalpercent,
            ]
        ]
    ]);
}
}
