<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class OpportunitySeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            'PROSPECTING',
            'PROPOSAL',
            'NEGOTIATION',
            'WON',
            'LOST'
        ];

        for ($i = 1; $i <= 10; $i++) {
            DB::table('opportunities')->insert([
                'lead_id' => rand(1, 10),
                'stage' => $stages[array_rand($stages)],
                'estimated_value' => rand(1000, 50000),
                'expected_close_date' => Carbon::now()->addDays(rand(7, 60)),
                'owner_id' => rand(1, 5),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
