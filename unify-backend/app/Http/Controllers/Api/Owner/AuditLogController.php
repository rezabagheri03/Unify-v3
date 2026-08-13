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

        // Round-2 (V2-08): F-04 producers write PLAIN JSON (no-secrets
        // policy); legacy rows (failed_login) are Crypt-encrypted. Try JSON
        // first, then decrypt, then raw — never a misleading "error" stub.
        $logs->getCollection()->transform(function ($log) {
            if ($log->details) {
                $plain = json_decode((string) $log->details, true);
                if (is_array($plain)) {
                    $log->details_decrypted = $plain;
                    return $log;
                }
                try {
                    $log->details_decrypted = json_decode(Crypt::decryptString($log->details), true);
                } catch (\Exception $e) {
                    $log->details_decrypted = ['raw' => (string) $log->details];
                }
            }
            return $log;
        });

        return response()->json($logs);
    }
}