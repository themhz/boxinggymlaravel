<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ClassException;
use App\Models\ClassModel;

class ClassExceptionsApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_class_exceptions()
    {
        $exception = ClassException::factory()->create();

        $this->getJson('/api/classes-exceptions')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $exception->id]);
    }

    /** @test */
    public function it_shows_a_single_class_exception()
    {
        $exception = ClassException::factory()->create();

        $this->getJson("/api/classes-exceptions/{$exception->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $exception->id]);
    }

    /** @test */
    public function it_creates_a_class_exception()
    {
        $class = ClassModel::factory()->create();

        $data = [
            'class_id' => $class->id,
            'date' => now()->addDay()->toDateString(),
            'reason' => 'Holiday',
        ];

        $this->postJson('/api/classes-exceptions', $data)
            ->assertStatus(201)
            ->assertJsonFragment(['reason' => 'Holiday']);

        $this->assertDatabaseHas('class_exceptions', $data);
    }

    /** @test */
    public function it_updates_a_class_exception()
    {
        $exception = ClassException::factory()->create([
            'reason' => 'Holiday',
        ]);

        $update = ['reason' => 'Teacher Sick'];

        $this->putJson("/api/classes-exceptions/{$exception->id}", $update)
            ->assertStatus(200)
            ->assertJsonFragment(['reason' => 'Teacher Sick']);

        $this->assertDatabaseHas('class_exceptions', [
            'id' => $exception->id,
            'reason' => 'Teacher Sick',
        ]);
    }

    /** @test */
    public function it_deletes_a_class_exception()
    {
        $exception = ClassException::factory()->create();

        $this->deleteJson("/api/classes-exceptions/{$exception->id}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Class exception deleted']);

        $this->assertDatabaseMissing('class_exceptions', [
            'id' => $exception->id,
        ]);
    }
}
