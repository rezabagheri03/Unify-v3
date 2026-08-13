<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $query = Faq::with(['specification.course'])
            ->orderByDesc('is_pinned')
            ->orderBy('created_at');

        if ($request->filled('specification_id')) {
            $query->where('specification_id', $request->specification_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:2000',
            'is_pinned' => 'nullable|boolean',
            'specification_id' => 'nullable|exists:course_specifications,id',
        ]);

        $faq = Faq::create([
            'id' => Str::uuid(),
            'specification_id' => $request->specification_id,
            'question' => InputSanitizer::clean($request->question, 500),
            'answer' => InputSanitizer::clean($request->answer, 2000),
            'is_pinned' => $request->boolean('is_pinned'),
            'created_by' => $user->id,
        ]);

        return response()->json($faq, 201);
    }
}
