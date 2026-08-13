<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NoticeBoard;
use App\Services\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NoticeBoardController extends Controller
{
    public function index(Request $request)
    {
        $query = NoticeBoard::with(['specification.course', 'creator'])
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if ($request->filled('specification_id')) {
            $query->where('specification_id', $request->specification_id);
        }

        return response()->json($query->orderByDesc('priority')->orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:2000',
            'priority' => 'nullable|in:low,medium,high',
            'banner_color' => 'nullable|string|max:7',
            'expires_at' => 'nullable|date',
            'specification_id' => 'nullable|exists:course_specifications,id',
        ]);

        $notice = NoticeBoard::create([
            'id' => Str::uuid(),
            'specification_id' => $request->specification_id,
            'title' => InputSanitizer::clean($request->title, 255),
            'content' => InputSanitizer::clean($request->content, 2000),
            'priority' => $request->get('priority', 'medium'),
            'banner_color' => $request->banner_color,
            'expires_at' => $request->expires_at,
            'created_by' => $user->id,
        ]);

        return response()->json($notice, 201);
    }
}
