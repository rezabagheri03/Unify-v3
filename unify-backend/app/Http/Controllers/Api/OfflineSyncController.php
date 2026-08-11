<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResourceRating;
use App\Models\ResourceStickyNote;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\AssignmentTracker;
use App\Models\StudentPassedCourse;
use App\Models\IdempotencyKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OfflineSyncController extends Controller
{
    private array $allowedTypes = ['rating', 'sticky', 'ticket_reply', 'assignment', 'curriculum_checkbox'];

    /**
     * SEC fix (offline-sync hardening): every queued item is validated and
     * authorized BEFORE it writes anything. The previous version trusted the
     * client payload wholesale (rating could be 9999, any ticket could receive
     * a reply, any assignment tracker could be hijacked by id).
     */
    private array $rules = [
        'rating' => [
            'resource_family_id' => 'required|string|max:64',
            'rating' => 'required|integer|between:1,5',
        ],
        'sticky' => [
            'resource_family_id' => 'required|string|max:64',
            'note' => 'required|string|max:2000',
        ],
        'ticket_reply' => [
            'ticket_id' => 'required|string|max:64',
            'body' => 'required|string|max:2000',
        ],
        'assignment' => [
            'id' => 'nullable|uuid',
            'specification_id' => 'required|string|max:64',
            'title' => 'required|string|max:255',
            'status' => 'nullable|in:pending,in_progress,done',
        ],
        'curriculum_checkbox' => [
            'course_id' => 'required|string|max:32',
            'entry_year' => 'nullable|integer|min:1300|max:1500',
            'passed' => 'nullable|boolean',
            'explicit_uncheck' => 'nullable|boolean',
        ],
    ];

    public function sync(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|max:50',
            'items.*.type' => 'required|in:' . implode(',', $this->allowedTypes),
            'items.*.payload' => 'required|array',
            'items.*.idempotency_key' => 'required|uuid',
        ]);

        $results = [];
        $user = $request->user();

        foreach ($data['items'] as $item) {
            $existing = IdempotencyKeys::where('key', $item['idempotency_key'])
                ->where('user_id', $user->id)
                ->first();
            if ($existing) {
                $results[] = ['id' => $item['idempotency_key'], 'status' => 'duplicate'];
                continue;
            }

            // 1) payload validation (per type)
            $validator = Validator::make($item['payload'], $this->rules[$item['type']]);
            if ($validator->fails()) {
                $results[] = [
                    'id' => $item['idempotency_key'],
                    'status' => 'failed',
                    'error' => 'invalid_payload',
                    'details' => $validator->errors()->first(),
                ];
                continue;
            }

            // 2) per-item authorization
            if (! $this->authorized($item['type'], $item['payload'], $user)) {
                $results[] = ['id' => $item['idempotency_key'], 'status' => 'failed', 'error' => 'forbidden'];
                continue;
            }

            try {
                $this->processItem($item['type'], $item['payload'], $user->id);

                IdempotencyKeys::create([
                    'id' => Str::uuid(),
                    'key' => $item['idempotency_key'],
                    'user_id' => $user->id,
                    'response_code' => 200,
                    'expires_at' => now()->addHours(24),
                ]);

                $results[] = ['id' => $item['idempotency_key'], 'status' => 'synced'];
            } catch (\Throwable $e) {
                // Never leak raw exception messages to clients.
                Log::warning('offline-sync item failed', [
                    'user_id' => $user->id,
                    'type' => $item['type'],
                    'error' => $e->getMessage(),
                ]);
                $results[] = ['id' => $item['idempotency_key'], 'status' => 'failed', 'error' => 'processing_failed'];
            }
        }

        return response()->json(['results' => $results]);
    }

    private function authorized(string $type, array $payload, $user): bool
    {
        return match ($type) {
            // A student may only reply to their OWN ticket.
            'ticket_reply' => Ticket::where('id', $payload['ticket_id'])
                ->where('student_id', $user->id)
                ->exists(),
            // Updating an existing tracker requires owning it (no id hijack).
            'assignment' => empty($payload['id'])
                || AssignmentTracker::where('id', $payload['id'])
                    ->where('student_id', $user->id)
                    ->exists(),
            // rating/sticky/curriculum rows are keyed by the caller id.
            default => true,
        };
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
        // Mirror RatingController: ratings of your OWN resource family must not
        // pollute the public average (the HTTP path sets is_self_rating).
        $familyProfessor = \App\Models\Resource::where('family_id', $payload['resource_family_id'])
            ->value('professor_id');

        ResourceRating::updateOrCreate(
            ['student_id' => $userId, 'resource_family_id' => $payload['resource_family_id']],
            ['rating' => (int) $payload['rating'], 'rated_at' => now(), 'is_self_rating' => $familyProfessor === $userId]
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
            ['id' => $payload['id'] ?? (string) Str::uuid()],
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
