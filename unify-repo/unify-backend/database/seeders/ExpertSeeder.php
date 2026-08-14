<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ExpertSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['id' => '300000001'],
            [
                'password_hash' => Hash::make('TempExpert!2026', ['rounds' => 12]),
                'first_name' => 'سارا',
                'last_name' => 'احمدی',
                'role' => 'expert',
                'department_id' => 'CS',
                // Round-2 (V2-02): lane staff need a functional lane to see tickets.
                'ticket_lane' => 'education',
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addDays(7),
            ]
        );

        $this->command->info('Expert created: 300000001 / TempExpert!2026');
    }
}
