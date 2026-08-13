<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

/**
 * Round-2 (V2-09): spec import validates + normalizes class times per row and
 * rejects unknown semesters — the lexicographic overlap engine no longer
 * trusts whatever strings the Excel file happened to carry.
 */
class SpecImportValidationTest extends TestCase
{
    use RefreshDatabase;

    private function xlsx(array $rows): UploadedFile
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);
        $tmp = tempnam(sys_get_temp_dir(), 'imp') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tmp);
        return new UploadedFile($tmp, 'specs.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function importAsExpert(array $rows)
    {
        $expert = User::factory()->create(['role' => 'expert']);
        return $this->actingAs($expert)->postJson('/api/v1/admin/import/specifications', ['file' => $this->xlsx($rows)]);
    }

    private function row(Course $course, User $prof, string $start, string $end, ?string $sem = null): array
    {
        return [$course->code, $prof->id, 'شنبه', $start, $end, null, null, $sem];
    }

    public function test_unpadded_valid_time_is_normalized(): void
    {
        $prof = User::factory()->professor()->create();
        $course = Course::factory()->create();

        $resp = $this->importAsExpert([
            ['درس', 'استاد', 'روز', 'شروع', 'پایان', 'مکان', 'امتحان', 'نیم‌سال'],
            $this->row($course, $prof, '8:00', '10:00', '1403-1'),
        ]);

        $resp->assertStatus(200)->assertJson(['message' => 'ایمپورت مشخصات: 1 مورد']);
        $this->assertDatabaseHas('course_specifications', ['course_id' => $course->id, 'time_start' => '08:00']);
    }

    public function test_garbage_time_fails_the_whole_file_with_a_report(): void
    {
        $prof = User::factory()->professor()->create();
        $course = Course::factory()->create();

        $resp = $this->importAsExpert([
            ['h'], // header skipped
            $this->row($course, $prof, '25:99', '10:00', '1403-1'),
        ]);

        // Error report = streamed xlsx, not the JSON success shape.
        $this->assertStringNotContainsString('ایمپورت موفق', $resp->getContent());
        $this->assertStringContainsString('attachment', (string) $resp->headers->get('content-disposition'));
        $this->assertDatabaseCount('course_specifications', 0);
    }

    public function test_inverted_range_is_rejected(): void
    {
        $prof = User::factory()->professor()->create();
        $course = Course::factory()->create();

        $this->importAsExpert([
            ['h'],
            $this->row($course, $prof, '10:00', '08:00', '1403-1'),
        ]);

        $this->assertDatabaseCount('course_specifications', 0);
    }

    public function test_unknown_semester_is_rejected(): void
    {
        $prof = User::factory()->professor()->create();
        $course = Course::factory()->create();

        $this->importAsExpert([
            ['h'],
            $this->row($course, $prof, '08:00', '10:00', '1500-1'),
        ]);

        $this->assertDatabaseCount('course_specifications', 0);
    }
}
