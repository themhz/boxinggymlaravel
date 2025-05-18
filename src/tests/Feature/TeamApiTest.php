<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class TeamApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    #[Test]
    public function it_lists_teams()
    {
        Team::factory()->count(3)->create();

        $response = $this->getJson('/api/teams');

        $response->assertOk()->assertJsonCount(3);
    }

    #[Test]
    public function it_shows_a_single_team()
    {
        $team = Team::factory()->create();

        $response = $this->getJson("/api/teams/{$team->id}");

        $response->assertOk()->assertJsonFragment(['id' => $team->id]);
    }

    #[Test]
    public function it_creates_a_team()
    {
        $this->authenticate();
        $data = [
            'name' => 'Ramos PLC',
            'description' => 'Multi-layered static function',
            'logo' => 'https://placeimg.com/100/100/any'
        ];

        $response = $this->postJson('/api/teams', $data);

        $response->assertCreated()->assertJsonFragment(['name' => 'Ramos PLC']);
        $this->assertDatabaseHas('teams', $data);
    }

    #[Test]
    public function it_updates_a_team()
    {
        $this->authenticate();
        $team = Team::factory()->create();

        $update = ['name' => 'Updated Team'];

        $response = $this->putJson("/api/teams/{$team->id}", $update);

        $response->assertOk()->assertJsonFragment($update);
        $this->assertDatabaseHas('teams', $update);
    }

    #[Test]
    public function it_deletes_a_team()
    {
        $this->authenticate();
        $team = Team::factory()->create();

        $response = $this->deleteJson("/api/teams/{$team->id}");

        //$response->assertNoContent();
        $response->assertOk()->assertJson(['message' => 'Deleted successfully']);
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }
}
