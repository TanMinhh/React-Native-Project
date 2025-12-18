<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = ['IN_PROGRESS', 'DONE'];

        for ($i = 1; $i <= 10; $i++) {
            $dueDate = rand(0, 1)
                ? Carbon::now()->addDays(rand(1, 14))
                : Carbon::now()->subDays(rand(1, 7));

            DB::table('tasks')->insert([
                'title' => 'Task ' . $i,
                'lead_id' => rand(1, 10),
                'opportunity_id' => rand(1, 10),
                'due_date' => $dueDate,
                'status' => $statuses[array_rand($statuses)],
                'assigned_to' => rand(1, 5),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
