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
        $staffs = DB::table('users')->where('role', 'staff')->pluck('id');
        $assignedDefault = $staffs->first() ?? 1;

        for ($i = 1; $i <= 10; $i++) {
            $dueDate = rand(0, 1)
                ? Carbon::now()->addDays(rand(1, 14))
                : Carbon::now()->subDays(rand(1, 7));

            DB::table('tasks')->insert([
                'type' => 'CALL',
                'title' => 'Task ' . $i,
                'lead_id' => rand(1, 10),
                'opportunity_id' => rand(1, 10),
                'due_date' => $dueDate,
                'status' => $statuses[array_rand($statuses)],
                'assigned_to' => $staffs->random() ?: $assignedDefault,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
