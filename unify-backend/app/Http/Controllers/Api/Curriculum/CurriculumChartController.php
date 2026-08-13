<?php

namespace App\Http\Controllers\Api\Curriculum;

use App\Http\Controllers\Controller;
use App\Models\CurriculumChart;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CurriculumChartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = CurriculumChart::with('department');

        if (in_array($user->role, ['student', 'expert', 'head_of_dept'])) {
            $query->where('department_id', $user->department_id);
        }

        if ($request->filled('entry_year')) {
            $query->where('entry_year', $request->entry_year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderByDesc('entry_year')->get());
    }

    public function store(Request $request)
    {
        // Round-2 (V2-04): authoring is a staff capability (docs/ROLES) — the
        // method inherited only the generic throttle group, so any student
        // could mint draft charts and spam heads via submitForApproval.
        if (! in_array($request->user()->role, ['expert', 'admin'], true)) {
            return response()->json(['message' => 'ایجاد نمودار فقط برای کارشناس و مدیر مجاز است', 'code' => 'FORBIDDEN'], 403);
        }
        $request->validate([
            'entry_year' => 'required|integer',
            'chart_data' => 'required|array',
        ]);

        $chart = CurriculumChart::create([
            'id' => Str::uuid(),
            'department_id' => $request->user()->department_id,
            'entry_year' => $request->entry_year,
            'chart_data' => $request->chart_data,
            'status' => 'draft',
            'version' => 1,
        ]);

        return response()->json($chart, 201);
    }

    public function submitForApproval(Request $request, $id)
    {
        // Round-2 (V2-04): submitting for approval is part of authoring —
        // students could otherwise trigger head-of-dept notifications.
        if (! in_array($request->user()->role, ['expert', 'admin'], true)) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }
        $chart = CurriculumChart::findOrFail($id);
        if ($chart->department_id !== $request->user()->department_id) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }
        $chart->update(['status' => 'pending_approval']);

        // Round-2: headOfDeptId used to fall back to a hardcoded owner id
        // ('990000001') that may not exist — FK-crashing the whole submit.
        // No head-of-dept → notify admins instead, exactly like the F-08
        // approver rule. Never a magic id.
        $targetIds = $this->approvalTargets($chart->department_id);
        foreach ($targetIds as $tid) {
            Notification::create([
                'id' => Str::uuid(),
                'user_id' => $tid,
                'type' => 'resource_new',
                'title' => 'نمودار درسی در انتظار تأیید',
                'body' => 'ورودی ' . $chart->entry_year,
                'priority' => 'high',
                'created_at' => now(),
            ]);
            \Illuminate\Support\Facades\Cache::forget("notifications:unread:{$tid}");
        }

        return response()->json(['message' => 'ارسال برای تأیید انجام شد']);
    }

    public function approve(Request $request, $id)
    {
        if (! in_array($request->user()->role, ['head_of_dept', 'admin', 'owner'])) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }
        $chart = CurriculumChart::findOrFail($id);
        $chart->update([
            'status' => 'approved',
            'approver_id' => $request->user()->id,
            'approved_at' => now(),
            'version' => $chart->version + 1,
        ]);

        return response()->json(['message' => 'نمودار تأیید شد']);
    }

    public function reject(Request $request, $id)
    {
        if (! in_array($request->user()->role, ['head_of_dept', 'admin', 'owner'])) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }
        $chart = CurriculumChart::findOrFail($id);
        $chart->update(['status' => 'draft']);

        return response()->json(['message' => 'نمودار برگشت داده شد']);
    }

    /** Approver targets: the dept's head(s), else all admins (never a ghost id). */
    private function approvalTargets(string $departmentId): \Illuminate\Support\Collection
    {
        $heads = \App\Models\User::where('role', 'head_of_dept')
            ->where('department_id', $departmentId)
            ->pluck('id');
        if ($heads->isNotEmpty()) {
            return $heads;
        }
        \Illuminate\Support\Facades\Log::warning('curriculum submit without head_of_dept — notifying admins', ['department_id' => $departmentId]);
        return \App\Models\User::whereIn('role', ['admin', 'owner'])->pluck('id');
    }
}
