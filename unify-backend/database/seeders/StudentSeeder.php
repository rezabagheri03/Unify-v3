<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    private const FIRST_NAMES = ['علی','سارا','محمد','زهرا','حسین','فاطمه','رضا','مریم','امیر','نگار','مهدی','الهام','حامد','نازنین','کیان','شیما','پارسا','آیدا','آرش','رویا','بهرام','درسا','سینا','پریسا','نیما','یاسمن','کاوه','مهسا','فرهاد','شیرین'];

    private const LAST_NAMES = ['احمدی','کریمی','حسینی','موسوی','جعفری','قاسمی','صادقی','رحیمی','نادری','عباسی','کاظمی','مرادی','شریفی','طاهری','نوری','مهدوی','فرهادی','بهرامی','گلشنی','زمانی'];

    private const STATUSES = ['normal','normal','normal','normal','normal','normal','normal','conditional','gpa_a','final_semester'];

    public function run(): void
    {
        // Pre-compute a single temp-password hash and reuse it: argon2id is slow
        // (~200ms/hash) and 600 students would take minutes otherwise. Seed data
        // shares one documented temp password, exactly like the other role seeders.
        $tempHash = Hash::make('TempStudent!2026', ['rounds' => 12]);

        // 1) Exact rows from seed_users_600.csv (20 sample students)
        $csv = database_path('seed-data/seed_users_600.csv');
        $seeded = 0;
        if (file_exists($csv)) {
            $rows = array_map('str_getcsv', file($csv));
            array_shift($rows); // header
            foreach ($rows as $row) {
                [$id, $first, $last, $role, $dept, $status] = array_pad($row, 6, null);
                if (! $id) {
                    continue;
                }
                $this->createStudent(trim($id), trim($first), trim($last), trim($status) ?: 'normal', $tempHash);
                $seeded++;
            }
        }

        // 2) Generate remaining students to reach 600 (IDs 400100001..400100600)
        $generated = 0;
        for ($i = $seeded + 1; $i <= 600; $i++) {
            $id = '4001' . str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $first = self::FIRST_NAMES[($i * 7) % count(self::FIRST_NAMES)];
            $last = self::LAST_NAMES[($i * 13) % count(self::LAST_NAMES)];
            $status = self::STATUSES[$i % count(self::STATUSES)];
            $this->createStudent($id, $first, $last, $status, $tempHash);
            $generated++;
        }

        $this->command->info("Students seeded: {$seeded} from CSV + {$generated} generated = 600 total. Temp password: TempStudent!2026");
    }

    private function createStudent(string $id, string $firstName, string $lastName, string $status, string $tempHash): void
    {
        User::updateOrCreate(
            ['id' => $id],
            [
                'password_hash' => $tempHash,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'role' => 'student',
                'department_id' => 'CS',
                'academic_status_declared' => in_array($status, ['normal','conditional','gpa_a','final_semester']) ? $status : 'normal',
                'academic_status_last_declared_at' => now(),
                'academic_status_declaration_count' => 1,
                'is_honor_system_acknowledged' => true,
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addDays(7),
            ]
        );
    }
}
