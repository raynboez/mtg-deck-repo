<?php
// app/Console/Commands/BackfillMMR.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MMRService;
use App\Models\Matches;
use App\Models\MatchParticipant;
use Illuminate\Support\Facades\DB;

class BackfillMMR extends Command
{
    protected $signature = 'mmr:backfill 
                            {--season= : Only backfill matches of a specific season}
                            {--start-from= : Start from match ID}
                            {--dry-run : Show what would be done without saving}';
    
    protected $description = 'Backfill MMR for existing matches';

    
    public function handle()
    {
        $mmrService = app(MMRService::class);
        $dryRun = $this->option('dry-run');

        if (!$dryRun) {
            DB::table('match_participants')->update([
                'mmr_before' => null,
                'mmr_after' => null,
                'mmr_change' => null
            ]);
        }

        $query = Matches::with('participants')
            ->orderBy('played_at', 'asc')
            ->orderBy('match_id', 'asc');

        if ($this->option('season')) {
            $query->where('match_type', $this->option('season'));
        }

        $matches = $query->get();
        $totalMatches = $matches->count();

        $this->info("Processing {$totalMatches} matches..." . ($dryRun ? ' (DRY RUN)' : ''));

        $bar = $this->output->createProgressBar($totalMatches);

        $playerMMR = [];

        foreach ($matches as $match) {
            try {
                if ($match->participants->isEmpty()) {
                    continue;
                }

                $playersData = $match->participants->map(function($participant) {
                    return [
                        'user_id' => $participant->user_id,
                        'is_winner' => $participant->is_winner,
                        'order_lost' => $participant->order_lost,
                        'turn_lost' => $participant->turn_lost,
                        'first_blood' => $participant->first_blood,
                        'motm' => $participant->motm,
                    ];
                })->toArray();
                $mmrChanges = $mmrService->calculateMatchMMR($playersData, $match->match_type);


                if (!$dryRun) {
                    foreach ($match->participants as $participant) {
                        $userId = $participant->user_id;
                        $mmrData = $mmrChanges[$userId] ?? ['change' => 0, 'position' => 0];
                        
                        $mmrBefore = $this->getPlayerCurrentMMR($userId, $match->match_type);

                        $participant->update([
                            'mmr_before' => $mmrBefore,
                            'mmr_change' => $mmrData['change'],
                            'mmr_after' => $mmrBefore + $mmrData['change'],
                        ]);

                        $playerMMR[$userId][$match->match_type] = $mmrBefore + $mmrData['change'];
                    }
                }

            } catch (\Exception $e) {
                $this->error("Error with match {$match->match_id}: " . $e->getMessage());
            }

        }


        $this->info("\nBackfill completed!");
    }

    private function getPlayerCurrentMMR($userId, $format)
    {
        $latestParticipant = DB::table('match_participants')
            ->join('matches', 'match_participants.match_id', '=', 'matches.match_id')
            ->where('match_participants.user_id', $userId)
            ->where('matches.match_type', $format)
            ->whereNotNull('match_participants.mmr_after')
            ->orderBy('matches.played_at', 'DESC')
            ->first();
        
        return $latestParticipant->mmr_after ?? app(MMRService::class)->getStartingMMR();
    }
}