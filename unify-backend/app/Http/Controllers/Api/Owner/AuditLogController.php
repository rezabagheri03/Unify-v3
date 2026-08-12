<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->orderBy('timestamp', 'desc')
            ->paginate(50);

        // Decrypt details for owner
        $logs->getCollection()->transform(function ($log) {
            if ($log->details) {
                try {
                    $log->details_decrypted = json_decode(Crypt::decryptString($log->details), true);
                } catch (\Exception $e) {
                    $log->details_decrypted = ['error' => 'Decryption failed'];
                }
            }
            return $log;
        });

        return response()->json($logs);
    }
}