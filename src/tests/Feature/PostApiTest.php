<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Post;
use Laravel\Sanctum\Sanctum;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_lists_posts()
    {
        Post::factory()->count(3)->create();
        $response = $this->getJson('/api/posts');
        $response->assertOk()->assertJsonCount(3);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_a_single_post()
    {
        $post = Post::factory()->create();
        $response = $this->getJson("/api/posts/{$post->id}");
        $response->assertOk()->assertJsonFragment(['id' => $post->id]);
    }

    // #[\PHPUnit\Framework\Attributes\Test]
    // public function it_creates_a_post()
    // {
    //     $this->authenticate();

    //     $data = ['title' => 'New Post', 'body' => 'Post body content.'];
    //     $response = $this->postJson('/api/posts', $data);

    //     $response->assertCreated()->assertJsonFragment($data);
    //     $this->assertDatabaseHas('posts', $data);
    // }

    // #[\PHPUnit\Framework\Attributes\Test]
    // public function it_updates_a_post()
    // {
    //     $this->authenticate();

    //     $post = Post::factory()->create();
    //     $updateData = ['title' => 'Updated Title', 'body' => 'Updated body.'];

    //     $response = $this->putJson("/api/posts/{$post->id}", $updateData);

    //     $response->assertOk()->assertJsonFragment($updateData);
    //     $this->assertDatabaseHas('posts', $updateData);
    // }

    // #[\PHPUnit\Framework\Attributes\Test]
    // public function it_deletes_a_post()
    // {
    //     $this->authenticate();

    //     $post = Post::factory()->create();

    //     $response = $this->deleteJson("/api/posts/{$post->id}");

    //     $response->assertNoContent();
    //     $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    // }
}