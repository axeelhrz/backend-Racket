<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tournament;
use App\Models\TournamentParticipant;

class SyncTournamentParticipants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tournaments:sync-participants';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync tournament participant counts with actual data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting tournament participant count synchronization...');

        $tournaments = Tournament::all();
        $updated = 0;

        foreach ($tournaments as $tournament) {
            $actualCount = TournamentParticipant::where('tournament_id', $tournament->id)
                ->whereIn('status', ['registered', 'confirmed'])
                ->count();

            if ($tournament->current_participants !== $actualCount) {
                $tournament->update(['current_participants' => $actualCount]);
                $this->line("Updated tournament '{$tournament->name}': {$tournament->current_participants} -> {$actualCount}");
                $updated++;
            }
        }

        $this->info("Synchronization complete. Updated {$updated} tournaments.");
        return 0;
    }
}