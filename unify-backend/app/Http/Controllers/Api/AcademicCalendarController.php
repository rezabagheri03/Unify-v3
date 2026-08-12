<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicCalendar;
use App\Services\ShamsiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AcademicCalendarController extends Controller
{
    public function index(Request $request)
    {
        $events = AcademicCalendar::where('is_active', true)
            ->orderBy('start_date_g')
            ->get()
            ->map(function ($event) {
                $event->shamsi_start = ShamsiService::toShamsi($event->start_date_g);
                $event->shamsi_end = ShamsiService::toShamsi($event->end_date_g);
                return $event;
            });

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'start_date_g' => 'required|date',
            'end_date_g' => 'required|date',
            'event_type' => 'required|string',
        ]);

        $event = AcademicCalendar::create([
            'id' => Str::uuid(),
            'title' => $request->title,
            'description' => $request->description,
            'start_date_g' => $request->start_date_g,
            'end_date_g' => $request->end_date_g,
            'shamsi_original_start' => ShamsiService::toShamsi($request->start_date_g),
            'shamsi_original_end' => ShamsiService::toShamsi($request->end_date_g),
            'event_type' => $request->event_type,
            'is_university_wide' => $request->boolean('is_university_wide', false),
            'color_code' => $request->color_code,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($event, 201);
    }
}