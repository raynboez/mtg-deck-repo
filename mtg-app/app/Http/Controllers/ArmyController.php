<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Army;
use Inertia\Inertia;
class ArmyController extends Controller
{
    public function userArmies(Request $request)
    {
        $user = $request->user()->user_id;
        $decks = DB::table('armies')->where('user_id', $user)->get();
        return response()->json($decks);
    }

    public function getUsers(Request $request)
    {
        $users = DB::table('users')->select('user_id', 'name')->get();
        return response()->json($users);
    }

    public function show($id)
    {
        $deck = Army::findOrFail($id);
        $id = $deck->deck_id;
        return Inertia::render('Deck', [
            'deck_id' => $id
        ]);
    }
}
