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

    public function getArmy(Request $request, $id)
    {
        $user = $request->user()->user_id;
        $army = Army::findOrFail($id);
        $personalwins = count(DB::table('warhammer_match_participants')->where('user_id', $user)->where('army_id', $id)->where('is_winner', 1)->get());
        $personalgames = count(DB::table('warhammer_match_participants')->where( 'user_id', $user)->where('army_id', $id)->get());
        $personalloss = $personalgames - $personalwins;
        try{
        $personalpercent = number_format(($personalwins / $personalgames) * 100); 
        }
        catch(DivisionByZeroError){
            $personalpercent = 0;
        }
        $wins = count(DB::table('warhammer_match_participants')->where('army_id', $id)->where('is_winner', 1)->get());
        $games = count(DB::table('warhammer_match_participants')->where('army_id', $id)->get());
        $loss = $games - $wins;
        try{
        $percent = number_format(($wins / $games) * 100); 
        }
        catch(DivisionByZeroError){
            $percent = 0;
        }

        if($personalgames != $games){
            $armystats = $wins . "-" . $loss . " (" . $percent . "% of $games).\nPersonal Win-Loss: " . $personalwins . "-" . $personalloss . " (" . $personalpercent . "% of $personalgames)";
        } else {
            $armystats = $wins . "-" . $loss . " (" . $percent . "% of $games)";
        }

        return response()->json(
            [
                'army' => $army,
                'armystats' => $armystats,
            ]
        );
    }
}
