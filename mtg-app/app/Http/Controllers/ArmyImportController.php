<?php

namespace App\Http\Controllers;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use App\Models\Army;
use App\Enums\GameMode;
use App\Enums\Faction;
use App\Enums\SubfactionAstartes;
use App\Enums\SubfactionChaos;
use App\Enums\SubfactionImperium;
use App\Enums\SubfactionXenos;

class ArmyImportController extends Controller
{
    
    public function import(Request $request)
    {
        $validSubfactions = array_merge(
        SubfactionAstartes::cases(), SubfactionChaos::cases(), SubfactionImperium::cases(), SubfactionXenos::cases()
        );
        set_time_limit(0);
        $validated = $request->validate([
            'army_name'=>'required|string',            
            'game_mode'=>['required', 'string', Rule::enum(GameMode::class)],
            'faction' => ['required', 'string', Rule::enum(Faction::class)],
            'army_description'=>'nullable|string',
            'points' => 'nullable|integer',
            'subfaction' => 'nullable|string',
            'army_link' => 'nullable|string',
            'army_list' => 'nullable|string',
        ]);

        $user = auth()->user();
        $gamemode = GameMode::from($validated['game_mode']);
        $faction = Faction::from($validated['faction']);
        $army = Army::create([
                'name' => $validated['army_name'],
                'description' => $validated['army_description'],
                'user_id' => $user->user_id,
                'game_mode' => $gamemode,
                'faction'=>$faction->value,
                'subfaction'=>$validated['subfaction'],
                'points'=>$validated['points'],
                'army_link'=>$validated['army_link'],
                'list'=>$validated['army_list'],
        ]);

        return response()->json([
            'message' => 'Army import complete',
        ]);
    }

}
