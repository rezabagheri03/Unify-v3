<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ExcelImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_import_users()
    {
        $owner = User::factory()->create(['role' => 'owner']);
        Storage::fake('local');

        // Build a real .xlsx (PhpSpreadsheet) so the importer can read it.
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['شماره دانشجویی', 'نام', 'نام خانوادگی', 'نقش', 'دانشکده', 'وضعیت'],
            ['500200001', 'علی', 'احمدی', 'student', 'CS', 'normal'],
            ['500200002', 'مریم', 'کریمی', 'student', 'CS', 'gpa_a'],
        ]);
        $tmp = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tmp);
        $file = new \Illuminate\Http\UploadedFile($tmp, 'users.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($owner)->postJson('/api/v1/owner/users/bulk-import', [
            'file' => $file
        ]);

        $response->assertStatus(200);
    }
}