<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResourceRating;
use App\Models\ResourceStickyNote;
use App\Models\TicketReply;
use App\Models\AssignmentTracker;
use App\Models\CurriculumChart;
use App\Models\StudentPassedCourse;
use App\Models\IdempotencyKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class OfflineSyncController extends Controller
{
    private array $allowedTypes = ['rating', 'sticky', 'ticket_reply', 'assignment', 'curriculum_checkbox'];

    public function sync(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|max:50',
            'items.*.type' => 'required|in:' . implode(',', $this->allowedTypes),
            'items.*.payload' => 'required',
            'items.*.idempotency_key' => 'required|uuid',
        ]);

        $results = [];

        foreach ($data['items'] as $item) {
            $existing = IdempotencyKeys::where('key', $item['idempotency_key'])->first();
            if ($existing) {
                $results[] = ['id' => $item['idempotency_key'], 'status' => 'duplicate'];
                continue;
            }

            try {
                $this->processItem($item['type'], $item['payload'], $request->user()->id);

                IdempotencyKeys::create([
                    'id' => Str::uuid(),
                    'key' => $item['idempotency_key'],
                    'user_id' => $request->user()->id,
                    'response_code' => 200,
                    'expires_at' => now()->addHours(24),
                ]);

                $results[] = ['id' => $item['idempotency_key'], 'status' => 'synced'];
            } catch (\Exception $e) {
                $results[] = ['id' => $item['idempotency_key'], 'status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return response()->json(['results' => $results]);
    }

    private function processItem(string $type, array $payload, string $userId)
    {
        match ($type) {
            'rating' => $this->processRating($payload, $userId),
            'sticky' => $this->processSticky($payload, $userId),
            'ticket_reply' => $this->processTicketReply($payload, $userId),
            'assignment' => $this->processAssignment($payload, $userId),
            'curriculum_checkbox' => $this->processCurriculumCheckbox($payload, $userId),
        };
    }

    private function processRating(array $payload, string $userId)
    {
        ResourceRating::updateOrCreate(
            ['student_id' => $userId, 'resource_family_id' => $payload['resource_family_id']],
            ['rating' => $payload['rating'], 'rated_at' => now(), 'is_self_rating' => false]
        );
    }

    private function processSticky(array $payload, string $userId)
    {
        $sanitized = strip_tags($payload['note']);
        $encrypted = Crypt::encryptString($sanitized);

        ResourceStickyNote::updateOrCreate(
            ['student_id' => $userId, 'resource_family_id' => $payload['resource_family_id']],
            ['note' => $encrypted]
        );
    }

    private function processTicketReply(array $payload, string $userId)
    {
        TicketReply::create([
            'id' => Str::uuid(),
            'ticket_id' => $payload['ticket_id'],
            'sender_id' => $userId,
            'body' => strip_tags($payload['body']),
            'sent_at' => now(),
            'is_staff' => false,
        ]);
    }

    private function processAssignment(array $payload, string $userId)
    {
        AssignmentTracker::updateOrCreate(
            ['id' => $payload['id'] ?? Str::uuid()],
            [
                'student_id' => $userId,
                'specification_id' => $payload['specification_id'],
                'title' => strip_tags($payload['title']),
                'status' => $payload['status'] ?? 'pending',
            ]
        );
    }

    private function processCurriculumCheckbox(array $payload, string $userId)
    {
        // OR-merge (F19): once a course is marked passed it stays passed unless
        // the student explicitly un-checks it with a confirmation flag.
        if (empty($payload['course_id'])) {
            throw new \InvalidArgumentException('course_id is required');
        }

        $entryYear = $payload['entry_year'] ?? 1401;

        $existing = StudentPassedCourse::where('student_id', $userId)
            ->where('course_id', $payload['course_id'])
            ->where('entry_year', $entryYear)
            ->first();

        if (! empty($payload['passed'])) {
            if ($existing) {
                $existing->update(['passed' => true]);
            } else {
                StudentPassedCourse::create([
                    'id' => (string) Str::uuid(),
                    'student_id' => $userId,
                    'course_id' => $payload['course_id'],
                    'entry_year' => $entryYear,
                    'passed' => true,
                    'grade' => null,
                    'created_at' => now(),
                ]);
            }
        } elseif ($existing && empty($payload['explicit_uncheck'])) {
            // OR merge: keep passed=true unless explicitly un-checked
            $existing->update(['passed' => true]);
        } elseif ($existing && ! empty($payload['explicit_uncheck'])) {
            $existing->update(['passed' => false]);
        }
    }
}