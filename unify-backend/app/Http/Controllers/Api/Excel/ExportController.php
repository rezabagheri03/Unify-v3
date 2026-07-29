<?php

namespace App\Http\Controllers\Api\Excel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends Controller
{
    public function exportUsers(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'شماره دانشجویی');
        $sheet->setCellValue('B1', 'نام');
        $sheet->setCellValue('C1', 'نام خانوادگی');
        $sheet->setCellValue('D1', 'نقش');

        // Add sample data
        $sheet->setCellValue('A2', '400123456');
        $sheet->setCellValue('B2', 'علی');
        $sheet->setCellValue('C2', 'احمدی');
        $sheet->setCellValue('D2', 'student');

        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'users-export.xlsx');
    }
}