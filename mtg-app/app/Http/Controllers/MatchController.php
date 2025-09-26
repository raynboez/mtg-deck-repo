<?php

namespace App\Http\Controllers;

use App\Models\Matches;
use App\Models\MatchParticipant;
use App\Rules\DeckIdOrBorrow;
use App\Services\MMRService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'players' => 'required|array',
            'players.*.user_id' => 'required|exists:users,user_id',
            'players.*.deck_id' => ['required', new DeckIdOrBorrow],
            'players.*.starting_life' => 'required|integer',
            'players.*.final_life' => 'nullable|integer',
            'players.*.turn_order' => 'required|integer',
            'players.*.order_lost' => 'nullable|integer',
            'players.*.turn_lost' => 'nullable|integer',
            'players.*.is_winner' => 'required|boolean',
            'players.*.first_blood' => 'required|boolean',
            'players.*.motm' => 'required|boolean',
            'players.*.borrow_user_id' => 'required_if:players.*.deck_id,borrow|exists:users,user_id',
            'players.*.borrow_deck_id' => 'required_if:players.*.deck_id,borrow|exists:decks,deck_id',
            'date_played' => 'required|date',
            'format' => 'required|string',
            'totalTurns' => 'required|integer',
            'bracket' => 'required|integer',
        ]);

        Log::info('Match data received:', $validated);
        $mmrService = app(MMRService::class);
        $mmrChanges = $mmrService->calculateMatchMMR($validated['players'], $validated['format']);
        DB::beginTransaction();

        try {
            $match = Matches::create([
                'played_at' => $validated['date_played'],
                'match_type' => $validated['format'],
                'number_of_players' => count($validated['players']),
                'total_turns' => $validated['totalTurns'],
                'bracket' => $validated['bracket']
            ]);

            foreach ($validated['players'] as $playerData) {
                $userId = $playerData['user_id'];
                $mmrData = $mmrChanges[$userId] ?? ['change' => 0, 'position' => 0];
                $currentMMR = $this->getPlayerCurrentMMR($userId, $validated['format']);


                if($playerData['deck_id'] === "borrow"){
                        MatchParticipant::create([
                        'match_id' => $match->match_id,
                        'user_id' => $playerData['user_id'],
                        'deck_id' => $playerData['borrow_deck_id'],
                        'is_winner' => $playerData['is_winner'],
                        'starting_life' => $playerData['starting_life'],
                        'final_life' => $playerData['final_life'],
                        'turn_order' => $playerData['turn_order'],
                        'order_lost' => $playerData['order_lost'] | 0,
                        'turn_lost' => $playerData['turn_lost'] | 0,
                        'first_blood' => $playerData['first_blood'],
                        'motm' => $playerData['motm'] | 0,
                        'mmr_before' => $currentMMR,
                        'mmr_change' => $mmrData['change'],
                        'mmr_after' => $currentMMR + $mmrData['change'],
                    ]);
                } else {
                        MatchParticipant::create([
                        'match_id' => $match->match_id,
                        'user_id' => $playerData['user_id'],
                        'deck_id' => $playerData['deck_id'],
                        'is_winner' => $playerData['is_winner'],
                        'starting_life' => $playerData['starting_life'],
                        'final_life' => $playerData['final_life'],
                        'turn_order' => $playerData['turn_order'],
                        'order_lost' => $playerData['order_lost'] | 0,
                        'turn_lost' => $playerData['turn_lost'] | 0,
                        'first_blood' => $playerData['first_blood'],
                        'motm' => $playerData['motm'] | 0,
                        'mmr_before' => $currentMMR,
                        'mmr_change' => $mmrData['change'],
                        'mmr_after' => $currentMMR + $mmrData['change'],
                    ]);
                }
                
            }

            DB::commit();

            \Log::info('Match created successfully', [
                'match_id' => $match->match_id,
                'participants_count' => count($validated['players'])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Match recorded successfully',
                'match_id' => $match->match_id,
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

    private function getPlayerCurrentMMR($userId, $format)
    {
        $latestParticipant = DB::table('match_participants')
            ->join('matches', 'match_participants.match_id', '=', 'matches.match_id')
            ->where('match_participants.user_id', $userId)
            ->where('matches.match_type', $format)
            ->orderBy('matches.played_at', 'DESC')
            ->first();
        
        return $latestParticipant->mmr_after ?? app(MMRService::class)->getStartingMMR();
    }
}