<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Department;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

/**
 * Post-audit F-03 + F-08: form publishing is scope-bound to the caller's role
 * (expert ⇒ own department, never university-level; admin ⇒ either) and a
 * published form actually notifies the affected students (F10).
 */
class FormsScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        // Base TestCase already seeds CS + semester reference rows.
        Department::updateOrCreate(['id' => 'MATH'], ['name_fa' => 'ریاضی', 'name_en' => 'MATH']);
    }

    public function test_expert_cannot_publish_university_level_form(): void
    {
        $expert = User::factory()->create(['role' => 'expert', 'department_id' => 'CS']);

        $this->actingAs($expert)->post('/api/v1/forms', [
            'title' => 'فرم', 'file' => $this->fakePdf('f.pdf'),
            'is_university_level' => true,
        ])->assertStatus(403)->assertJson(['code' => 'UNIVERSITY_FORMS_ADMIN_ONLY']);
    }

    public function test_expert_cannot_publish_to_another_department(): void
    {
        $expert = User::factory()->create(['role' => 'expert', 'department_id' => 'CS']);

        $this->actingAs($expert)->post('/api/v1/forms', [
            'title' => 'فرم', 'file' => $this->fakePdf('f.pdf'),
            'department_id' => 'MATH',
        ])->assertStatus(403)->assertJson(['code' => 'DEPT_SCOPE_VIOLATION']);
    }

    public function test_expert_form_is_bound_to_own_department_and_notifies_dept_students(): void
    {
        $expert = User::factory()->create(['role' => 'expert', 'department_id' => 'CS']);
        User::factory()->count(2)->create(['role' => 'student', 'department_id' => 'CS']);
        User::factory()->create(['role' => 'student', 'department_id' => 'MATH']);

        $this->actingAs($expert)->post('/api/v1/forms', [
            'title' => 'فرم انتقالی', 'file' => $this->fakePdf('f.pdf'),
        ])->assertStatus(201);

        $this->assertSame(2, Notification::where('title', 'فرم جدید دانشکده')->count());
        $this->assertSame(0, Notification::where('title', 'فرم جدید دانشگاهی')->count());
        $this->assertDatabaseHas('forms', ['title' => 'فرم انتقالی', 'department_id' => 'CS', 'is_university_level' => false]);
    }

    public function test_admin_university_form_notifies_all_students(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'department_id' => null]);
        User::factory()->count(2)->create(['role' => 'student', 'department_id' => 'CS']);
        User::factory()->create(['role' => 'student', 'department_id' => 'MATH']);

        $this->actingAs($admin)->post('/api/v1/forms', [
            'title' => 'فرم دانشگاهی', 'file' => $this->fakePdf('u.pdf'),
            'is_university_level' => true,
        ])->assertStatus(201);

        $this->assertSame(3, Notification::where('title', 'فرم جدید دانشگاهی')->count());
    }
}
