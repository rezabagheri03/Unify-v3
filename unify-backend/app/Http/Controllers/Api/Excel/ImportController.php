<?php

namespace App\Http\Controllers\Api\Excel;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSpecification;
use App\Models\Department;
use App\Models\User;
use App\Services\ShamsiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ImportController extends Controller
{
    // TODO-020/D-001: the import scale of this product is one intake
    // (~600 students). Capping at the scale keeps the synchronous-bounded
    // design honest: worst case ≈ 600 reduced-profile hashes + 600 inserts
    // inside one request budget, with set_time_limit as the outer guard.
    private const MAX_ROWS = 600;

    private const ROLE_MAP = [
        'دانشجو' => 'student', 'student' => 'student',
        'استاد' => 'professor', 'professor' => 'professor',
        'کارشناس' => 'expert', 'expert' => 'expert',
        'مدیر' => 'admin', 'admin' => 'admin',
        'مالک' => 'owner', 'owner' => 'owner',
        'رییس گروه' => 'head_of_dept', 'head_of_dept' => 'head_of_dept',
    ];

    public function importUsers(Request $request)
    {
        @set_time_limit(120); // TODO-020: bounded synchronous import (D-001)

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (count($rows) < 2) {
            return response()->json(['message' => 'فایل خالی است', 'code' => 'EMPTY_FILE'], 422);
        }
        if (count($rows) - 1 > self::MAX_ROWS) {
            return response()->json(['message' => 'حداکثر ۶۰۰ ردیف در هر فایل مجاز است؛ فایل‌های بزرگ‌تر را تقسیم کنید', 'code' => 'ROW_LIMIT'], 422);
        }

        // Validate header row (row 1) — exact-match mapping (Persian or English)
        $header = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $rows[0] ?? []);
        $headerMap = [
            'شماره دانشجویی' => 'student',
            'نام' => 'first_name',
            'نام خانوادگی' => 'last_name',
            'نقش' => 'role',
            'دانشکده' => 'department_id',
            'وضعیت' => 'academic_status',
        ];
        $mapped = array_map(fn ($h) => $headerMap[$h] ?? $h, $header);
        $required = ['student', 'first_name', 'last_name', 'role', 'department_id', 'academic_status'];
        $missing = array_diff($required, $mapped);
        if (! empty($missing)) {
            return response()->json(['message' => 'ستون‌های الزامی فایل ناقص است', 'code' => 'BAD_HEADER', 'missing' => array_values($missing)], 422);
        }

        $errors = [];
        $created = 0;
        $seenIds = [];
        // TODO-020: two-pass import. Pass 1 validates WITHOUT writing or
        // hashing, so a doomed file costs milliseconds and never burns the
        // shared-host CPU budget on password hashes that rollback would
        // discard. Pass 2 (below) only runs for fully-clean files.
        $validated = [];

        // PERF-04 fix: resolve pre-existing user ids and valid departments with
        // 2 queries TOTAL instead of 2 EXISTS queries per row (up to 2000 rows).
        $candidateIds = [];
        $deptIds = [];
        foreach (array_slice($rows, 1) as $row) {
            $row = array_pad($row, 6, null);
            $candidateIds[] = trim((string) $row[0]);
            $deptIds[] = trim((string) $row[4]);
        }
        $existingIds = User::whereIn('id', array_filter($candidateIds))->pluck('id')->flip();
        $validDeptIds = Department::whereIn('id', array_filter(array_unique($deptIds)))->pluck('id')->flip();

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNo = $i + 2;
            $row = array_pad($row, 6, null);
            [$id, $first, $last, $roleFa, $deptId, $statusFa] = $row;

                $id = trim((string) $id);
                $role = self::ROLE_MAP[trim((string) $roleFa)] ?? null;

                if (! preg_match('/^\d{6,10}$/', $id)) {
                    $errors[] = ['row' => $rowNo, 'error' => 'شماره دانشجویی نامعتبر', 'data' => $row];
                    continue;
                }
                if (isset($seenIds[$id])) {
                    $errors[] = ['row' => $rowNo, 'error' => 'شناسه تکراری در فایل', 'data' => $row];
                    continue;
                }
                if ($existingIds->has($id)) {
                    $errors[] = ['row' => $rowNo, 'error' => "شناسه {$id} قبلا وجود دارد", 'data' => $row];
                    continue;
                }
                if (! $role) {
                    $errors[] = ['row' => $rowNo, 'error' => "نقش نامعتبر: {$roleFa}", 'data' => $row];
                    continue;
                }
                if (! $validDeptIds->has((string) $deptId)) {
                    $errors[] = ['row' => $rowNo, 'error' => "دانشکده نامعتبر: {$deptId}", 'data' => $row];
                    continue;
                }
                if ($statusFa && ! in_array($statusFa, ['normal', 'conditional', 'gpa_a', 'final_semester'])) {
                    $errors[] = ['row' => $rowNo, 'error' => 'وضعیت تحصیلی نامعتبر', 'data' => $row];
                    continue;
                }

            $seenIds[$id] = true;
            $validated[] = compact('id', 'first', 'last', 'role', 'deptId', 'statusFa');
        }

        if (! empty($errors)) {
            return $this->generateErrorReport($errors);
        }

        // Pass 2: file is fully clean — now pay the hashing cost, atomically.
        DB::beginTransaction();
        try {
            foreach ($validated as $v) {
                User::create([
                    'id' => $v['id'],
                    'first_name' => $v['first'],
                    'last_name' => $v['last'],
                    'role' => $v['role'],
                    'department_id' => $v['deptId'],
                    'academic_status_declared' => $v['statusFa'] ?: 'normal',
                    'is_honor_system_acknowledged' => true,
                    // PERF-04 fix: temporary passwords are random, short-lived
                    // (7d) and must-change — use the same reduced-work-factor
                    // argon2id profile as the envelope flow (argon2id default
                    // time_cost=4 made a 600-row import take ~1 minute of pure
                    // hashing inside one web request).
                    'password_hash' => Hash::make(Str::random(12), ['memory_cost' => 65536, 'time_cost' => 1, 'threads' => 1]),
                    'must_change_password' => true,
                    'temporary_password_expires_at' => now()->addDays(7),
                ]);
                $created++;
            }

            DB::commit();
            return response()->json(['message' => "ایمپورت موفق: {$created} کاربر اضافه شد", 'created' => $created]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطای سیستمی: ' . $e->getMessage(), 'code' => 'IMPORT_ERROR'], 500);
        }
    }

    public function importCourses(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:5120']);
        $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray();
        if (count($rows) - 1 > self::MAX_ROWS) {
            return response()->json(['message' => 'حداکثر ۶۰۰ ردیف در هر فایل مجاز است', 'code' => 'ROW_LIMIT'], 422);
        }

        $errors = [];
        $created = 0;
        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $i => $row) {
                $rowNo = $i + 2;
                $row = array_pad($row, 4, null);
                [$code, $name, $credits, $deptId] = $row;
                $code = trim((string) $code);
                if (! $code) {
                    continue;
                }
                if (! Department::where('id', $deptId)->exists()) {
                    $errors[] = ['row' => $rowNo, 'error' => "دانشکده نامعتبر: {$deptId}", 'data' => $row];
                    continue;
                }
                Course::updateOrCreate(
                    ['code' => $code],
                    ['id' => $code, 'name' => $name, 'credits' => (int) $credits, 'department_id' => $deptId, 'is_active' => true]
                );
                $created++;
            }
            if (! empty($errors)) {
                DB::rollBack();
                return $this->generateErrorReport($errors);
            }
            DB::commit();
            return response()->json(['message' => "ایمپورت دروس: {$created} درس"]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطای سیستمی: ' . $e->getMessage(), 'code' => 'IMPORT_ERROR'], 500);
        }
    }

    public function importSpecifications(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:5120']);
        $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray();

        $days = ['شنبه' => 'sat', 'یکشنبه' => 'sun', 'دوشنبه' => 'mon', 'سهشنبه' => 'tue', 'چهارشنبه' => 'wed', 'پنجشنبه' => 'thu', 'جمعه' => 'fri'];
        $errors = [];
        $created = 0;
        DB::beginTransaction();
        try {
            foreach (array_slice($rows, 1) as $i => $row) {
                $rowNo = $i + 2;
                $row = array_pad($row, 8, null);
                [$courseCode, $profId, $dayFa, $start, $end, $location, $finalShamsi, $semesterId] = $row;

                if (! Course::where('code', trim($courseCode))->exists()) {
                    $errors[] = ['row' => $rowNo, 'error' => "درس نامعتبر: {$courseCode}", 'data' => $row];
                    continue;
                }
                if (! User::where('id', trim($profId))->where('role', 'professor')->exists()) {
                    $errors[] = ['row' => $rowNo, 'error' => "استاد نامعتبر: {$profId}", 'data' => $row];
                    continue;
                }
                $day = $days[str_replace("\u{200C}", '', trim($dayFa))] ?? null;
                if (! $day) {
                    $errors[] = ['row' => $rowNo, 'error' => "روز نامعتبر: {$dayFa}", 'data' => $row];
                    continue;
                }
                if ($finalShamsi && ! ShamsiService::isValid(trim($finalShamsi))) {
                    $errors[] = ['row' => $rowNo, 'error' => "تاریخ شمسی نامعتبر: {$finalShamsi}", 'data' => $row];
                    continue;
                }

                CourseSpecification::create([
                    'id' => Str::uuid(),
                    'course_id' => trim($courseCode),
                    'professor_id' => trim($profId),
                    'day_of_week' => $day,
                    'time_start' => $start,
                    'time_end' => $end,
                    'location' => $location ?: null,
                    'exam_date_final_g' => $finalShamsi ? ShamsiService::toGregorian(trim($finalShamsi)) : null,
                    'shamsi_original_final' => $finalShamsi ? trim($finalShamsi) : null,
                    'semester_id' => $semesterId ?: '1403-1',
                    'is_active' => true,
                    'is_next_day' => false,
                ]);
                $created++;
            }
            if (! empty($errors)) {
                DB::rollBack();
                return $this->generateErrorReport($errors);
            }
            DB::commit();
            return response()->json(['message' => "ایمپورت مشخصات: {$created} مورد"]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطای سیستمی: ' . $e->getMessage(), 'code' => 'IMPORT_ERROR'], 500);
        }
    }

    private function generateErrorReport(array $errors)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ردیف');
        $sheet->setCellValue('B1', 'خطا');
        $sheet->setCellValue('C1', 'داده خام');
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);

        $row = 2;
        foreach ($errors as $error) {
            $sheet->setCellValue('A' . $row, $error['row']);
            $sheet->setCellValue('B' . $row, $error['error']);
            $sheet->setCellValue('C' . $row, json_encode($error['data'], JSON_UNESCAPED_UNICODE));
            $sheet->getStyle('B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(fn () => $writer->save('php://output'), 'error-report-' . time() . '.xlsx');
    }
}
