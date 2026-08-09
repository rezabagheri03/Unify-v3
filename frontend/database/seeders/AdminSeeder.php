<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['id' => '500000001'],
            [
                'password_hash' => Hash::make('TempAdmin!2026', ['rounds' => 12]),
                'first_name' => 'مریم',
                'last_name' => 'حسینی',
                'role' => 'admin',
                'department_id' => 'CS',
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addDays(7),
            ]
        );

        $this->command->info('Admin created: 500000001 / TempAdmin!2026');
    }
}
