<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        // Create Owner user (IT handout style - temp password)
        $owner = User::firstOrCreate(
            ['id' => '990000001'],
            [
                'password_hash' => Hash::make('TempOwner!2026', ['rounds' => 12]),
                'first_name' => 'مالک',
                'last_name' => 'سیستم',
                'role' => 'owner',
                'department_id' => null,
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addDays(7),
                'is_honor_system_acknowledged' => true,
            ]
        );

        $this->command->info('Owner created: 990000001 / TempOwner!2026 (expires in 7 days)');
    }
}