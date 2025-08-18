<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Student;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Validation\ValidationException;


class StudentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Gate used by the controller (admin-only create/update).
        // We DO NOT persist is_admin; we set it in-memory on the user instance.
        Gate::define('students.create', function (User $user = null) {
            return $user && ($user->is_admin ?? false);
        });
    }

    // -----------------------
    // index()
    // -----------------------
    #[Test]
    public function it_index_returns_students_with_user(): void
    {
        $user = User::factory()->create();
        Student::factory()->create(['user_id' => $user->id]);

        $controller = app(\App\Http\Controllers\StudentController::class);
        $response = $controller->index();

        $data = $response->getData(true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('user', $data[0]);
        $this->assertEquals($user->id, $data[0]['user']['id']);
    }

    // -----------------------
    // show($id)
    // -----------------------
    #[Test]
    public function it_show_returns_single_student_with_user(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create(['user_id' => $user->id]);

        $controller = app(\App\Http\Controllers\StudentController::class);
        $response = $controller->show($student->id);

        $data = $response->getData(true);
        $this->assertEquals($student->id, $data['id']);
        $this->assertEquals($user->id, $data['user']['id']);
    }

    #[Test]
    public function it_show_throws_404_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $controller = app(\App\Http\Controllers\StudentController::class);
        $controller->show(999999);
    }
    
    
    // -----------------------
    // store(Request)
    // -----------------------
    #[Test]
    public function it_store_requires_admin_gate(): void
    {
        // Not logged in -> AuthorizationException (Gate needs a user)
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $controller = app(\App\Http\Controllers\StudentController::class);
        $req = Request::create('/api/students', 'POST', [
            'name' => 'John', 'email' => 'john@example.com', 'user_id' => User::factory()->create()->id,
        ]);
        $controller->store($req);
    }
    #[Test]
    public function it_store_creates_student_when_admin(): void
    {
        $admin = User::factory()->create();
        $admin->setAttribute('is_admin', true); // runtime-only; not persisted
        $this->actingAs($admin);

        $linkedUser = User::factory()->create();

        $controller = app(\App\Http\Controllers\StudentController::class);
        $req = Request::create('/api/students', 'POST', [
            'name'  => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '210-0000000',
            'dob'   => '1990-01-01',
            'image' => null,
            'user_id' => $linkedUser->id,
        ]);
        $req->headers->set('Accept', 'application/json'); // controller does this too

        $response = $controller->store($req);
        $data = $response->getData(true);

        $this->assertEquals('Student created successfully', $data['message']);
        $this->assertEquals('jane@example.com', $data['student']['email']);
        $this->assertDatabaseHas('students', [
            'email' => 'jane@example.com',
            'user_id' => $linkedUser->id,
        ]);
    }

    #[Test]
    public function it_store_fails_when_user_id_does_not_exist(): void
    {
        $admin = User::factory()->create(); $admin->setAttribute('is_admin', true);
        $this->actingAs($admin);

        $controller = app(\App\Http\Controllers\StudentController::class);
        $req = Request::create('/api/students', 'POST', [
            'name' => 'Bad', 'email' => 'new@example.com', 'user_id' => 999999, // not exists
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->store($req);
    }
    #[Test]
    public function it_store_fails_when_email_not_unique(): void
    {
        $admin = User::factory()->create(); $admin->setAttribute('is_admin', true);
        $this->actingAs($admin);

        // Seed an existing student with dup email
        $existingUser = User::factory()->create();
        Student::factory()->create([
            'email' => 'dup@example.com',
            'user_id' => $existingUser->id,
        ]);

        $controller = app(\App\Http\Controllers\StudentController::class);
        $req = Request::create('/api/students', 'POST', [
            'name' => 'Dup', 'email' => 'dup@example.com', 'user_id' => User::factory()->create()->id,
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->store($req);
    }
    #[Test]
    public function it_store_fails_when_user_id_already_linked_to_student(): void
    {
        $admin = User::factory()->create(); $admin->setAttribute('is_admin', true);
        $this->actingAs($admin);

        $existingUser = User::factory()->create();
        Student::factory()->create([
            'email' => 'someone@example.com',
            'user_id' => $existingUser->id,
        ]);

        $controller = app(\App\Http\Controllers\StudentController::class);
        $req = Request::create('/api/students', 'POST', [
            'name' => 'DupLink', 'email' => 'another@example.com', 'user_id' => $existingUser->id, // already used
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->store($req);
    }

    // -----------------------
    // update(Request, Student)
    // -----------------------
    #[Test]
    public function it_update_requires_admin_gate(): void
    {
        $student = Student::factory()->create();

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        $controller = app(\App\Http\Controllers\StudentController::class);
        $req = Request::create("/api/students/{$student->id}", 'PUT', ['name' => 'X']);
        $controller->update($req, $student);
    }

    #[Test]
    public function it_update_edits_student_when_admin(): void
    {
        $admin = User::factory()->create();
        $admin->setAttribute('is_admin', true);
        $this->actingAs($admin);

        $student = Student::factory()->create(['email' => 'old@example.com']);

        $controller = app(\App\Http\Controllers\StudentController::class);
        $req = Request::create("/api/students/{$student->id}", 'PUT', [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
        $req->headers->set('Accept', 'application/json');

        $response = $controller->update($req, $student);
        $data = $response->getData(true);

        $this->assertEquals('Student updated successfully', $data['message']);
        $this->assertEquals('New Name', $data['student']['name']);
        $this->assertEquals('new@example.com', $data['student']['email']);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    #[Test]
    public function it_update_fails_when_email_not_unique(): void
    {
        $admin = User::factory()->create();
        $admin->setAttribute('is_admin', true);
        $this->actingAs($admin);

        $student = Student::factory()->create([
            'email'   => 'keep@example.com',
            'user_id' => User::factory()->create()->id,
        ]);

        $other = Student::factory()->create([
            'email'   => 'other@example.com',
            'user_id' => User::factory()->create()->id,
        ]);

        $controller = app(\App\Http\Controllers\StudentController::class);

        $req = Request::create("/api/students/{$student->id}", 'PUT', [
            'email' => 'other@example.com', // duplicate email
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->update($req, $student);
    }

    #[Test]
    public function it_update_fails_when_user_id_not_unique(): void
    {
        $admin = User::factory()->create();
        $admin->setAttribute('is_admin', true);
        $this->actingAs($admin);

        $student = Student::factory()->create([
            'email'   => 'keep2@example.com',
            'user_id' => User::factory()->create()->id,
        ]);

        $other = Student::factory()->create([
            'email'   => 'other2@example.com',
            'user_id' => User::factory()->create()->id,
        ]);

        $controller = app(\App\Http\Controllers\StudentController::class);

        $req = Request::create("/api/students/{$student->id}", 'PUT', [
            'user_id' => $other->user_id, // duplicate user link
        ]);
        $req->headers->set('Accept', 'application/json');

        $this->expectException(ValidationException::class);
        $controller->update($req, $student);
    }

    // -----------------------
    // destroy($id)
    // -----------------------
    #[Test]
    public function it_destroy_deletes_student_and_returns_204(): void
    {
        $student = Student::factory()->create();

        $controller = app(\App\Http\Controllers\StudentController::class);
        $response = $controller->destroy($student->id);

        $this->assertEquals(204, $response->status());
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }

    #[Test]
    public function it_destroy_throws_404_when_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $controller = app(\App\Http\Controllers\StudentController::class);
        $controller->destroy(999999);
    }
}
