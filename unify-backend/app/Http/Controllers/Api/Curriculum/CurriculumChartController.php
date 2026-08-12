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
        $chart = CurriculumChart::findOrFail($id);
        if ($chart->department_id !== $request->user()->department_id) {
            return response()->json(['message' => 'دسترسی ندارید', 'code' => 'FORBIDDEN'], 403);
        }
        $chart->update(['status' => 'pending_approval']);

        Notification::create([
            'id' => Str::uuid(),
            'user_id' => $this->headOfDeptId($chart->department_id),
            'type' => 'resource_new',
            'title' => 'نمودار درسی در انتظار تأیید',
            'body' => 'ورودی ' . $chart->entry_year,
            'priority' => 'high',
            'created_at' => now(),
        ]);

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

    private function headOfDeptId(string $departmentId): string
    {
        return \App\Models\User::where('role', 'head_of_dept')
            ->where('department_id', $departmentId)
            ->value('id') ?? '990000001';
    }
}
