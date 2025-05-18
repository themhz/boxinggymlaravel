<?php

namespace Tests\Feature;

use App\Models\ClassModel;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ClassApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    #[Test]
    public function it_lists_classes()
    {
        ClassModel::factory()->count(3)->create();
        $response = $this->getJson('/api/classes');
        $response->assertOk()->assertJsonCount(3);
    }

     #[Test]
     public function it_shows_a_single_class()
     {
         $class = ClassModel::factory()->create();
         $response = $this->getJson("/api/classes/{$class->id}");
         $response->assertOk()->assertJsonFragment(['id' => $class->id]);
     }

     #[Test]
     public function it_creates_a_class()
     {
         $this->authenticate();
         $lesson = Lesson::factory()->create();

         $data = [
             'lesson_id' => $lesson->id,
             'start_time' => '10:00:00',
             'end_time' => '11:00:00',
             'day' => 'Monday',
             'capacity' => 15,
        ];
        $response = $this->postJson('/api/classes', $data);
         $response->assertCreated()->assertJsonFragment(['day' => 'Monday']);
         $this->assertDatabaseHas('classes', $data);
     }

    #[Test]
    public function it_updates_a_class()
    {
        $this->authenticate();
        $class = ClassModel::factory()->create();

        $data = [
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'capacity' => 20,
        ];

        $response = $this->putJson("/api/classes/{$class->id}", $data);
        $response->assertOk()->assertJsonFragment(['capacity' => 20]);
        $this->assertDatabaseHas('classes', array_merge(['id' => $class->id], $data));
    }

    #[Test]
    public function it_deletes_a_class()
    {
        $this->authenticate();
        $class = ClassModel::factory()->create();

        $response = $this->deleteJson("/api/classes/{$class->id}");
        $response->assertNoContent();
        $this->assertDatabaseMissing('classes', ['id' => $class->id]);
    }
}
