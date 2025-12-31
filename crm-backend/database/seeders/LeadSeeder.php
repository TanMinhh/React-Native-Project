<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Team;
use App\Models\User;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $teams = Team::with(['manager', 'salesMembers'])->get();
        
        $sources = ['website', 'facebook', 'referral', 'cold_call', 'event'];
        $statuses = ['LEAD', 'CONTACTED', 'CARING', 'PURCHASED', 'NO_NEED'];
        
        $leadCount = 1;
        
        foreach ($teams as $team) {
            $manager = $team->manager;
            $salesMembers = $team->salesMembers;
            
            if ($salesMembers->isEmpty()) {
                continue;
            }
            
            // Create leads for each team (distributed among sales members)
            foreach (range(1, 8) as $i) {
                $assignedTo = $salesMembers->random();
                
                DB::table('leads')->insert([
                    'full_name' => "Khách hàng {$leadCount}",
                    'email' => "customer{$leadCount}@email.com",
                    'phone_number' => '09' . str_pad($leadCount, 8, '0', STR_PAD_LEFT),
                    'company' => "Công ty " . chr(64 + $leadCount),
                    'status' => $statuses[array_rand($statuses)],
                    'source' => $sources[array_rand($sources)],
                    'owner_id' => $assignedTo->id,
                    'assigned_to' => $assignedTo->id,
                    'assigned_by' => $manager->id,
                    'assigned_at' => now()->subDays(rand(1, 30)),
                    'team_id' => $team->id,
                    'last_activity_at' => rand(0, 1) ? now()->subDays(rand(1, 15)) : null,
                    'unread_by_owner' => rand(0, 1),
                    'created_at' => now()->subDays(rand(1, 60)),
                    'updated_at' => now(),
                ]);
                
                $leadCount++;
            }
        }
        
        $this->command->info("Created " . ($leadCount - 1) . " leads distributed across all teams");
    }
}
