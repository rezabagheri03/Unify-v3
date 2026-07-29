<?php

namespace App\Http\Controllers\Api\Excel;

use App\Http\Controllers\Controller;
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
    public function importUsers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $errors = [];
        $rowNumber = 2;

        DB::beginTransaction();

        try {
            foreach ($sheet->getRowIterator(2) as $row) {
                $rowData = [];
                foreach ($row->getCellIterator() as $cell) {
                    $rowData[] = $cell->getValue();
                }

                // Expected columns: id, first_name, last_name, role, department_id, mobile
                if (count($rowData) < 5) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => 'تعداد ستون‌ها ناکافی است',
                        'data' => $rowData
                    ];
                    continue;
                }

                [$id, $firstName, $lastName, $role, $departmentId, $mobile] = $rowData;

                // Validation
                if (!preg_match('/^\d{9}$/', $id)) {
                    $errors[] = ['row' => $rowNumber, 'error' => 'شماره دانشجویی نامعتبر', 'data' => $rowData];
                    continue;
                }

                if (!in_array($role, ['student', 'professor', 'expert', 'admin', 'owner'])) {
                    $errors[] = ['row' => $rowNumber, 'error' => 'نقش نامعتبر', 'data' => $rowData];
                    continue;
                }

                // Create user
                User::updateOrCreate(
                    ['id' => $id],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'role' => $role,
                        'department_id' => $departmentId,
                        'password_hash' => Hash::make(Str::random(12)),
                        'must_change_password' => true,
                        'temporary_password_expires_at' => now()->addDays(7),
                    ]
                );

                $rowNumber++;
            }

            if (count($errors) > 0) {
                DB::rollBack();
                return $this->generateErrorReport($errors, $file->getClientOriginalName());
            }

            DB::commit();
            return response()->json(['message' => 'ایمپورت با موفقیت انجام شد']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطای سیستمی: ' . $e->getMessage()], 500);
        }
    }

    private function generateErrorReport(array $errors, string $originalName)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'ردیف');
        $sheet->setCellValue('B1', 'خطا');
        $sheet->setCellValue('C1', 'داده خام');

        $redFill = (new \PhpOffice\PhpSpreadsheet\Style\Fill())
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFFF0000');

        $row = 2;
        foreach ($errors as $error) {
            $sheet->setCellValue('A' . $row, $error['row']);
            $sheet->setCellValue('B' . $row, $error['error']);
            $sheet->setCellValue('C' . $row, json_encode($error['data']));

            // Highlight error column in red
            $sheet->getStyle('B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF0000');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'error-report-' . time() . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename);
    }
}