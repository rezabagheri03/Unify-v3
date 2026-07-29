<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfessorSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['id' => '200000001'],
            [
                'password_hash' => Hash::make('TempProf!2026', ['rounds' => 12]),
                'first_name' => 'دکتر',
                'last_name' => 'رضایی',
                'role' => 'professor',
                'department_id' => 'CS',
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addDays(7),
            ]
        );

        $this->command->info('Professor created: 200000001 / TempProf!2026');
    }
}
