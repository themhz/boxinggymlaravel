<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Team;
use App\Models\Teacher;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class TeacherApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;

    protected function authenticate()
    {
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    }

    #[Test]
    public function it_lists_teachers()
    {
        Teacher::factory()->count(3)->create();
        $response = $this->getJson('/api/teachers');
        $response->assertOk()->assertJsonCount(3);
    }

    #[Test]
    public function it_shows_a_single_teacher()
    {
        $teacher = Teacher::factory()->create();
        $response = $this->getJson("/api/teachers/{$teacher->id}");
        $response->assertOk()->assertJsonFragment(['id' => $teacher->id]);
    }

    #[Test]
    public function it_creates_a_teacher()
    {
        $this->authenticate();

        $data = [
            'name' => 'Test Teacher',
            'specialty' => 'Boxing',
            'bio' => 'Experienced coach with a focus on boxing.',
            'photo' => 'photo.jpg',
            'team_id' => $this->team->id,
            'user_id' => $this->user->id,
        ];

         $response = $this->postJson('/api/teachers', $data);
         $response->assertCreated()->assertJsonFragment(['name' => 'Test Teacher']);
         $this->assertDatabaseHas('teachers', $data);
     }

    // #[Test]
    // public function it_updates_a_teacher()
    // {
    //     $this->authenticate();

    //     $teacher = Teacher::factory()->create(['user_id' => $this->user->id, 'team_id' => $this->team->id]);
    //     $update = ['name' => 'Updated Teacher', 'specialty' => 'MMA'];

    //     $response = $this->putJson("/api/teachers/{$teacher->id}", $update);
    //     $response->assertOk()->assertJsonFragment($update);
    //     $this->assertDatabaseHas('teachers', $update);
    // }

    // #[Test]
    // public function it_deletes_a_teacher()
    // {
    //     $this->authenticate();

    //     $teacher = Teacher::factory()->create(['user_id' => $this->user->id, 'team_id' => $this->team->id]);
    //     $response = $this->deleteJson("/api/teachers/{$teacher->id}");
    //     $response->assertNoContent();
    //     $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
    // }
}
