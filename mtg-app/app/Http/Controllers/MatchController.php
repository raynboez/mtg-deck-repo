<?php

namespace App\Http\Controllers;

use App\Models\Matches;
use App\Models\MatchParticipant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MatchController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'match_type' => 'required|string|in:commander,tournament,casual',
            'notes' => 'nullable|string',
            'played_at' => 'required|date',
            'participants' => 'required|array|min:2',
            'participants.*.user_id' => 'required|exists:users,id',
            'participants.*.deck_id' => 'required|exists:decks,id',
            'participants.*.is_winner' => 'required|boolean',
            'participants.*.starting_life' => 'required|integer|min:1',
            'participants.*.final_life' => 'nullable|integer',
            'participants.*.turn_order' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated) {
            $match = Matches::create([
                'match_type' => $validated['match_type'],
                'number_of_players' => count($validated['participants']),
                'notes' => $validated['notes'] ?? null,
                'played_at' => $validated['played_at'],
            ]);

            foreach ($validated['participants'] as $participant) {
                MatchParticipant::create([
                    'match_id' => $match->id,
                    ...$participant
                ]);
            }

            return response()->json($match->load('participants.user', 'participants.deck'), 201);
        });
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