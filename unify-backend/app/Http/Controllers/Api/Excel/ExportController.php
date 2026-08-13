<?php

namespace App\Http\Controllers\Api\Excel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends Controller
{
    /**
     * Export users by role scope (F16):
     * - Expert: own department only
     * - Admin/Owner: all
     * - Student: forbidden
     */
    public function exportUsers(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'student') {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }

        $query = User::query();
        if ($user->role === 'expert' || $user->role === 'head_of_dept') {
            $query->where('department_id', $user->department_id);
        }

        $users = $query->orderBy('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['شماره دانشجویی', 'نام', 'نام خانوادگی', 'نقش', 'دانشکده', 'وضعیت تحصیلی', 'بن شده'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
        }

        $row = 2;
        foreach ($users as $u) {
            // Round-2 (V2-15): cells are typed from USER-CONTROLLED text
            // (onboarding names). PhpSpreadsheet auto-types '=...' as a
            // FORMULA — guard every cell against spreadsheet injection.
            $sheet->setCellValueByColumnAndRow(1, $row, $this->safeCell($u->id));
            $sheet->setCellValueByColumnAndRow(2, $row, $this->safeCell($u->first_name));
            $sheet->setCellValueByColumnAndRow(3, $row, $this->safeCell($u->last_name));
            $sheet->setCellValueByColumnAndRow(4, $row, $this->safeCell($u->role));
            $sheet->setCellValueByColumnAndRow(5, $row, $this->safeCell($u->department_id));
            $sheet->setCellValueByColumnAndRow(6, $row, $this->safeCell($u->academic_status_declared ?? '-'));
            $sheet->setCellValueByColumnAndRow(7, $row, $u->is_banned ? 'بله' : 'خیر');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(fn () => $writer->save('php://output'), 'users-export-' . date('Ymd') . '.xlsx');
    }

    /** Round-2 (V2-15): force formula-leading values to inert text. */
    private function safeCell($value): string
    {
        $v = (string) $value;
        return preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;
    }
}
