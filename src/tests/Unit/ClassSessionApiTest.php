<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\ClassModel;
use App\Models\ClassSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ClassSessionApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_class_sessions()
    {
        $class = ClassModel::factory()->create();
        ClassSession::factory()->count(3)->create(['class_id' => $class->id]);

        $response = $this->getJson('/api/classes-sessions');

        $response->assertStatus(200)
                 ->assertJsonStructure([[
                     'id',
                     'class_id',
                     'date',
                     'time_start',
                     'time_end'
                 ]]);
    }

    /** @test */
    public function it_shows_a_class_session()
    {
        $class = ClassModel::factory()->create();
        $session = ClassSession::factory()->create(['class_id' => $class->id]);

        $response = $this->getJson("/api/classes-sessions/{$session->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['id' => $session->id]);
    }

    /** @test */
    public function it_creates_a_class_session()
    {
        $class = ClassModel::factory()->create();

        $payload = [
            'class_id' => $class->id,
            'date' => now()->toDateString(),
            'time_start' => '10:00',
            'time_end' => '11:00',
        ];

        $response = $this->postJson('/api/classes-sessions', $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment(['class_id' => $class->id]);
    }

    /** @test */
    public function it_updates_a_class_session()
    {
        $class = ClassModel::factory()->create();
        $session = ClassSession::factory()->create([
            'class_id' => $class->id,
            'time_start' => '10:00',
            'time_end' => '11:00',
        ]);

        $payload = [
            'time_start' => '12:00',
            'time_end' => '13:00',
        ];

        $response = $this->putJson("/api/classes-sessions/{$session->id}", $payload);

        $response->assertStatus(200)
                 ->assertJsonFragment(['time_start' => '12:00']);
    }

    /** @test */
    public function it_deletes_a_class_session()
    {
        $class = ClassModel::factory()->create();
        $session = ClassSession::factory()->create(['class_id' => $class->id]);

        $response = $this->deleteJson("/api/classes-sessions/{$session->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Class session deleted']);
    }
}
