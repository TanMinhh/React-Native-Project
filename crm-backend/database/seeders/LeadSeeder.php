<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadSeeder extends Seeder
{
    public function run(): void
    {
        $owners = DB::table('users')->whereIn('role', ['admin', 'owner'])->pluck('id');
        $staffs = DB::table('users')->where('role', 'staff')->pluck('id');
        $defaultAssignee = $staffs->first();

        foreach (range(1, 10) as $i) {
            $assignedTo = $staffs->random();
            $ownerId = $owners->random();
            DB::table('leads')->insert([
                'full_name' => "Lead $i",
                'email' => "lead$i@mail.com",
                'phone_number' => '090000000' . $i,
                'company' => "Company $i",
                'status' => 'LEAD',
                'source' => 'website',
                'owner_id' => $ownerId,
                'assigned_to' => $assignedTo ?: $defaultAssignee,
                'assigned_by' => $ownerId,
                'assigned_at' => now(),
                'unread_by_owner' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
