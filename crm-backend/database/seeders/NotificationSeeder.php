<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['LEAD', 'TASK', 'SYSTEM'];

        for ($i = 1; $i <= 10; $i++) {
            DB::table('notifications')->insert([
                'user_id' => rand(1, 10),
                'type' => $types[array_rand($types)],
                'content' => 'Notification content ' . $i,
                'payload' => json_encode(['ref' => $i]),
                'is_read' => rand(0, 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
