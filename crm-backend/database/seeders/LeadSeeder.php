<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $owners = DB::table('users')->whereIn('role', ['admin', 'owner'])->pluck('id');

        foreach (range(1, 10) as $i) {
            DB::table('leads')->insert([
                'full_name' => "Lead $i",
                'email' => "lead$i@mail.com",
                'phone_number' => '090000000' . $i,
                'company' => "Company $i",
                'status' => 'NEW',
                'owner_id' => $owners->random(),
                'unread_by_owner' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
