<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class HeadOfDeptSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['id' => '400000001'],
            [
                'password_hash' => Hash::make('TempHead!2026', ['rounds' => 12]),
                'first_name' => 'محمد',
                'last_name' => 'کریمی',
                'role' => 'head_of_dept',
                'department_id' => 'CS',
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addDays(7),
            ]
        );

        $this->command->info('Head of Dept created: 400000001 / TempHead!2026');
    }
}
