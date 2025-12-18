<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LeadSeeder::class,
            OpportunitySeeder::class,
            TaskSeeder::class,
            ActivitySeeder::class,
            NotificationSeeder::class,
            AttachmentSeeder::class,
        ]);
    }
}
