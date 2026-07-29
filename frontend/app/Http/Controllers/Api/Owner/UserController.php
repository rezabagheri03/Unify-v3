<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

class UserController extends Controller
{
    public function bulkImport(Request $request)
    {
        // This calls the Excel ImportController logic
        return app(\App\Http\Controllers\Api\Excel\ImportController::class)->importUsers($request);
    }

    public function generateEnvelopeZip(Request $request)
    {
        $users = User::where('must_change_password', true)->take(600)->get();

        $zip = new ZipArchive();
        $zipFileName = storage_path('app/temp/envelopes_' . time() . '.zip');
        $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($users as $user) {
            $tempPassword = Str::random(12);
            $user->update([
                'password_hash' => Hash::make($tempPassword),
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addDays(7),
            ]);

            $qr = QrCode::size(180)->generate($user->id . '|' . $tempPassword);

            $pdf = Pdf::loadView('envelopes.it-handout', [
                'user' => $user,
                'tempPassword' => $tempPassword,
                'qr' => $qr,
            ]);

            $pdfContent = $pdf->output();
            $zip->addFromString("envelope-{$user->id}.pdf", $pdfContent);
        }

        $zip->close();

        return response()->download($zipFileName)->deleteFileAfterSend(true);
    }
}