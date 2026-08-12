<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Version-lifecycle negative guards (Security audit SEC-02 / TODO-044):
 * ownership on newVersion, role gates on approve/reject, pending-only
 * rejection, and parent restoration when a new version is rejected.
 */
class ResourceVersionGuardsTest extends TestCase
{
    use RefreshDatabase;

    private function pdfUpload(): UploadedFile
    {
        // Minimal but REAL pdf — validatedMime() runs finfo() on the bytes,
        // so UploadedFile::fake() shells (random bytes) get 422'd by design.
        $tmp = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($tmp, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<<>>\n%%EOF");

        return new UploadedFile($tmp, 'notes.pdf', null, null, true);
    }

    public function test_student_cannot_push_new_version_into_another_users_family(): void
    {
        $parent = Resource::factory()->create(); // professor-owned by default
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post("/api/v1/resources/{$parent->id}/new-version", ['file' => $this->pdfUpload()])
            ->assertStatus(403);
    }

    public function test_original_uploader_can_push_pending_new_version_and_parent_stays_live(): void
    {
        Storage::fake('local');
        $student = User::factory()->create(['role' => 'student']);
        $parent = Resource::factory()->create([
            'uploader_id' => $student->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($student)
            ->post("/api/v1/resources/{$parent->id}/new-version", ['file' => $this->pdfUpload()]);

        $response->assertStatus(201);
        $child = Resource::findOrFail($response->json('id'));
        $this->assertSame('pending', $child->status);
        $this->assertSame($parent->id, $child->previous_version_id);
        // SEC-02: supersede-before-approve must NOT happen for the pending path
        $this->assertFalse((bool) $parent->fresh()->is_superseded);
    }

    public function test_student_cannot_approve_or_reject(): void
    {
        $resource = Resource::factory()->pending()->create();
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->postJson("/api/v1/admin/resources/{$resource->id}/approve")
            ->assertStatus(403);
        $this->actingAs($student)
            ->postJson("/api/v1/admin/resources/{$resource->id}/reject")
            ->assertStatus(403);
    }

    public function test_reject_is_only_valid_from_pending_state(): void
    {
        $resource = Resource::factory()->create(['status' => 'approved']);
        $expert = User::factory()->create(['role' => 'expert']);

        $this->actingAs($expert)
            ->postJson("/api/v1/admin/resources/{$resource->id}/reject")
            ->assertStatus(400)
            ->assertJsonPath('code', 'INVALID_STATE');
    }

    public function test_rejecting_pending_version_restores_superseded_parent(): void
    {
        $parent = Resource::factory()->create([
            'status' => 'approved',
            'is_superseded' => true,
            'scheduled_hard_delete_at' => now()->addDays(30),
        ]);
        $child = Resource::factory()->pending()->create([
            'previous_version_id' => $parent->id,
            'family_id' => $parent->family_id ?? $parent->id,
        ]);
        $expert = User::factory()->create(['role' => 'expert']);

        $this->actingAs($expert)
            ->postJson("/api/v1/admin/resources/{$child->id}/reject", ['reason' => 'quality'])
            ->assertStatus(200);

        $this->assertSame('rejected', $child->fresh()->status);
        $this->assertNull($child->fresh()->temp_path);
        $this->assertFalse((bool) $parent->fresh()->is_superseded);
        $this->assertNull($parent->fresh()->scheduled_hard_delete_at);
    }
}
