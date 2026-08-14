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
        return app(\App\Http\Controllers\Api\Excel\ImportController::class)->importUsers($request);
    }

    /**
     * Generate a ZIP of IT-handout envelopes for all students with an unused
     * temp password (F01).
     *
     * Bulk-safe design:
     *  - Students only (never owner/admin/staff).
     *  - All 600 PDFs are generated FIRST; passwords are only committed AFTER
     *    every PDF succeeds (atomic — a mid-run failure can't corrupt logins).
     *  - set_time_limit is raised because 600 argon2id hashes + 600 PDFs
     *    exceed the default 30s; bulk temp hashes use a reduced argon2id cost.
     */
    public function generateEnvelopeZip(Request $request)
    {
        set_time_limit(600);

        // TODO-017: imported staff previously received unknown random passwords
        // because envelopes were student-only. The owner may now choose the
        // audience explicitly; the safe default stays students-only and the
        // owner role is always excluded from handouts.
        // TODO-024: limit/offset/department_id let the UI generate the ZIP in
        // bounded batches instead of one 600-PDF mega-request.
        $request->validate([
            'scope' => 'nullable|in:students,staff,all',
            'limit' => 'nullable|integer|min:1|max:600',
            'offset' => 'nullable|integer|min:0',
            'department_id' => 'nullable|string|max:32',
        ]);
        $scope = $request->get('scope', 'students');

        $query = User::where('must_change_password', true)
            ->where('role', '!=', 'owner')
            ->orderBy('id');
        if ($scope === 'students') {
            $query->where('role', 'student');
        } elseif ($scope === 'staff') {
            $query->whereIn('role', ['professor', 'expert', 'head_of_dept', 'admin']);
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }
        if ($request->filled('offset')) {
            $query->skip((int) $request->offset);
        }
        $users = $query->take((int) ($request->get('limit', 600)))->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'دانشجویی با رمز موقت در انتظار یافت نشد', 'code' => 'NO_ENVELOPES'], 404);
        }

        $zip = new ZipArchive();
        $zipFileName = storage_path('app/temp/envelopes_' . time() . '.zip');
        if (! is_dir(dirname($zipFileName))) {
            mkdir(dirname($zipFileName), 0775, true);
        }
        $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $pending = []; // password changes applied only after all PDFs succeed
        foreach ($users as $user) {
            $tempPassword = $this->generateTempPassword();
            $hash = Hash::make($tempPassword, ['memory_cost' => 65536, 'time_cost' => 1, 'threads' => 1]);
            $pending[] = ['user' => $user, 'hash' => $hash];

            $qr = QrCode::size(180)->generate($user->id . '|' . $tempPassword);
            $pdf = Pdf::loadView('envelopes.it-handout', [
                'user' => $user,
                'tempPassword' => $tempPassword,
                'qr' => $qr,
            ]);
            $zip->addFromString("envelope-{$user->id}.pdf", $pdf->output());
        }

        $zip->close();

        // All PDFs built OK — now (and only now) commit the new temp passwords.
        \Illuminate\Support\Facades\DB::transaction(function () use ($pending) {
            foreach ($pending as $p) {
                $p['user']->update([
                    'password_hash' => $p['hash'],
                    'must_change_password' => true,
                    'temporary_password_expires_at' => now()->addDays(7),
                ]);
            }
        });

        \App\Models\AuditLog::record($request->user()->id, 'envelopes_generated', 'user', null, $request, [
            'count' => count($pending), 'scope' => $scope,
        ]);
        return response()->download($zipFileName)->deleteFileAfterSend(true);
    }

    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $tempPassword = $this->generateTempPassword();

        $user->update([
            'password_hash' => Hash::make($tempPassword),
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addDays(7),
        ]);

        // SEC-03 fix: a reset instantly kills all existing sessions/tokens.
        $user->tokens()->delete();

        \App\Models\AuditLog::record($request->user()->id, 'password_reset', 'user', $user->id, $request);

        $qr = QrCode::size(180)->generate($user->id . '|' . $tempPassword);
        $pdf = Pdf::loadView('envelopes.it-handout', [
            'user' => $user,
            'tempPassword' => $tempPassword,
            'qr' => $qr,
        ]);

        return response($pdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="envelope-' . $user->id . '.pdf"');
    }

    public function ban(Request $request, $id)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        $user = User::findOrFail($id);

        if ($user->role === 'owner') {
            return response()->json(['message' => 'نمی‌توان مالک سیستم را بن کرد', 'code' => 'FORBIDDEN'], 403);
        }

        $user->update([
            'is_banned' => true,
            'banned_reason' => $request->reason ?? 'نقض قوانین',
            'banned_at' => now(),
            'banned_by' => $request->user()->id,
        ]);

        // SEC-03 fix: banning instantly kills all existing sessions/tokens.
        $user->tokens()->delete();

        \App\Models\AuditLog::record($request->user()->id, 'user_banned', 'user', $user->id, $request, [
            'reason' => $user->banned_reason,
        ]);

        return response()->json(['message' => 'کاربر بن شد', 'user' => $user->only(['id', 'is_banned', 'banned_reason'])]);
    }

    public function unban(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update([
            'is_banned' => false,
            'banned_reason' => null,
            'banned_at' => null,
            'banned_by' => null,
        ]);
        \App\Models\AuditLog::record($request->user()->id, 'user_unbanned', 'user', $user->id, $request);
        return response()->json(['message' => 'رفع بن انجام شد']);
    }

    /**
     * Owner analytics (PERF-15 fix): one aggregate endpoint instead of the
     * dashboard pulling the full users XLSX as text plus two paginated lists.
     */
    public function stats(Request $request)
    {
        $byRole = User::query()
            ->selectRaw('role, COUNT(*) as c')
            ->groupBy('role')
            ->pluck('c', 'role');

        $semester = \App\Models\Semester::where('is_current', true)->first();

        return response()->json([
            'users_by_role' => $byRole,
            'users_total' => $byRole->sum(),
            'users_banned' => User::where('is_banned', true)->count(),
            'users_pending_password' => User::where('must_change_password', true)->count(),
            'resources_pending' => \App\Models\Resource::where('status', 'pending')->count(),
            'resources_approved' => \App\Models\Resource::where('status', 'approved')->count(),
            'tickets_open' => \App\Models\Ticket::where('status', '!=', 'closed')->count(),
            'tickets_escalated' => \App\Models\Ticket::where('is_escalated', true)->count(),
            'storage_used_bytes' => (int) (\App\Models\SystemConfig::where('key', 'storage_used_bytes')->value('value') ?? 0),
            'current_semester' => $semester?->id,
        ]);
    }

    /** 12-char temp password with upper, lower, digit, special (F01). */
    private function generateTempPassword(): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnpqrstuvwxyz';
        $digits = '23456789';
        $special = '!@#$%^&*';
        $pool = [$upper, $lower, $digits, $special];

        // Guarantee at least one of each class, then fill and shuffle.
        $parts = [];
        foreach ($pool as $set) {
            $parts[] = $set[random_int(0, strlen($set) - 1)];
        }
        while (count($parts) < 12) {
            $set = $pool[array_rand($pool)];
            $parts[] = $set[random_int(0, strlen($set) - 1)];
        }
        shuffle($parts);
        return implode('', $parts);
    }
}
