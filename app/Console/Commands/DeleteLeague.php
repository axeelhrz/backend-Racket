<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\League;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteLeague extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'league:delete {name : The name of the league to delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a league and its associated admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $leagueName = $this->argument('name');
        
        // Find the league
        $league = League::where('name', $leagueName)->first();
        
        if (!$league) {
            $this->error("League '{$leagueName}' not found.");
            return 1;
        }
        
        $this->info("Found league: {$league->name} (ID: {$league->id})");
        
        // Check if league has clubs
        $clubsCount = $league->clubs()->count();
        if ($clubsCount > 0) {
            $this->error("Cannot delete league '{$leagueName}' because it has {$clubsCount} associated clubs.");
            $this->info("Please remove all clubs from this league first.");
            return 1;
        }
        
        // Confirm deletion
        if (!$this->confirm("Are you sure you want to delete the league '{$leagueName}'?")) {
            $this->info('Deletion cancelled.');
            return 0;
        }
        
        DB::beginTransaction();
        
        try {
            // Get the admin user
            $adminUser = $league->user;
            
            // Delete the league
            $league->delete();
            $this->info("League '{$leagueName}' deleted successfully.");
            
            // Delete the admin user if it exists and is only associated with this league
            if ($adminUser && $adminUser->role === 'liga') {
                $adminUser->delete();
                $this->info("Associated admin user '{$adminUser->name}' deleted successfully.");
            }
            
            DB::commit();
            $this->info("League deletion completed successfully!");
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->error("Error deleting league: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}