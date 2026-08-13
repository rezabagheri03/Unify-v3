<?php

namespace Tests;

use App\Models\Department;
use App\Models\Semester;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Seed the minimal reference data every feature test needs
     * (departments + a current semester), regardless of the seeder chain.
     */
    /**
     * Real minimal-PDF UploadedFile so magic-bytes (finfo) validation passes.
     */
    protected function fakePdf(string $name = 'file.pdf'): \Illuminate\Http\UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tmp, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");
        return new \Illuminate\Http\UploadedFile($tmp, $name, 'application/pdf', null, true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Only seed reference rows when migrations actually ran (tests without
        // RefreshDatabase — e.g. pure unit/health tests — may have no tables).
        if (\Illuminate\Support\Facades\Schema::hasTable('departments')) {
            Department::updateOrCreate(
                ['id' => 'CS'],
                ['name_fa' => 'مهندسی کامپیوتر', 'name_en' => 'Computer Engineering']
            );
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('semesters')) {
            Semester::updateOrCreate(
                ['id' => '1403-1'],
                [
                    'name' => 'نیم‌سال اول ۱۴۰۳',
                    'is_current' => true,
                    'global_state' => 'enrolling',
                    'start_date_g' => '2024-09-20 08:00:00',
                    'end_date_g' => '2025-01-20 18:00:00',
                ]
            );
        }
    }
}
