<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfessorSeeder extends Seeder
{
    /** Professor IDs used by seed_specifications_100.csv (P1001..P1003) + generated specs. */
    private const PROFESSOR_IDS = ['P1001','P1002','P1003','P1004','P1005','P1006','P1007','P1008','P1009','P1010','P1011','P1012','P1013','P1014','P1015','P1016','P1017','P1018','P1019','P1020'];

    private const LAST_NAMES = ['رضایی','کریمی','احمدی','حسینی','موسوی','جعفری','قاسمی','صادقی','رحیمی','نادری','عباسی','کاظمی','مرادی','شریفی','طاهری','نوری','مهدوی','فرهادی','گلشنی','بهرامی'];

    public function run(): void
    {
        $count = 0;
        foreach (self::PROFESSOR_IDS as $i => $id) {
            User::updateOrCreate(
                ['id' => $id],
                [
                    'password_hash' => Hash::make('TempProf!2026', ['rounds' => 12]),
                    'first_name' => 'دکتر',
                    'last_name' => self::LAST_NAMES[$i % count(self::LAST_NAMES)],
                    'role' => 'professor',
                    'department_id' => 'CS',
                    'must_change_password' => true,
                    'temporary_password_expires_at' => now()->addDays(7),
                ]
            );
            $count++;
        }

        // Keep the legacy professor account referenced by older tests/docs.
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

        $this->command->info("Professors seeded: {$count} (P1001..P1020) + 200000001. Temp password: TempProf!2026");
    }
}
