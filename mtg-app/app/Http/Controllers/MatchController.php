<?php

namespace App\Http\Controllers;

use App\Models\Matches;
use App\Models\MatchParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class MatchController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'players' => 'required|array',
            'players.*.user_id' => 'required|exists:users,user_id',
            'players.*.deck_id' => 'required|exists:decks,deck_id',
            'players.*.starting_life' => 'required|integer',
            'players.*.final_life' => 'nullable|integer',
            'players.*.turn_order' => 'required|integer',
            'players.*.order_lost' => 'nullable|integer',
            'players.*.winner' => 'required|boolean',
            'date_played' => 'required|date',
            'format' => 'required|string',
            'bracket' => 'required|integer',
        ]);

        Log::info('Match data received:', $validated);

        DB::beginTransaction();

        try {
            $match = Matches::create([
                'played_at' => $validated['date_played'],
                'match_type' => $validated['format'],
                'number_of_players' => count($validated['players']),
                'bracket' => $validated['bracket']
            ]);

            foreach ($validated['players'] as $playerData) {
                MatchParticipant::create([
                    'match_id' => $match->id,
                    'user_id' => $playerData['user_id'],
                    'deck_id' => $playerData['deck_id'],
                    'is_winner' => $playerData['winner'],
                    'starting_life' => $playerData['starting_life'],
                    'final_life' => $playerData['final_life'],
                    'turn_order' => $playerData['turn_order'],
                    'order_lost' => $playerData['order_lost'] | 0,
                    'turn_lost' => 0,
                ]);
            }

            DB::commit();

            \Log::info('Match created successfully', [
                'match_id' => $match->id,
                'participants_count' => count($validated['players'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Match recorded successfully',
                'match_id' => $match->id,
                'participants_count' => count($validated['players'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error creating match: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to record match',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function userMatches($userId)
    {
        $matches = Matches::whereHas('participants', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with(['participants.user', 'participants.deck'])
          ->orderBy('played_at', 'desc')
          ->paginate(20);

        return response()->json($matches);
    }
}