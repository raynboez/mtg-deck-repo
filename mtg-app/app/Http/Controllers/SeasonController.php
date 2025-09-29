<?php

namespace App\Http\Controllers;

use App\Models\Season;
use Illuminate\Http\Request;

class SeasonController extends Controller
{
    /**
     * Get all seasons
     */
    public function index()
    {
        $seasons = Season::orderBy('date_started', 'desc')->get();
        
        return response()->json($seasons);
    }

    public function getActiveSeasons()
    {
        $currentDate = now()->format('Y-m-d');
        
        $seasons = Season::where('date_ended', '>=', $currentDate)
            ->orderBy('date_started', 'asc')
            ->get();
            
        return response()->json($seasons);
    }

    public function getActiveSeasonId()
    {
        $currentDate = now()->format('Y-m-d');
        $season = Season::where('date_ended', '>=', $currentDate)
            ->where('id', '!=', 0)
            ->orderBy('date_started', 'asc')
            ->first();

        return $season ? $season->id : null;
    }
}