<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class StudentPaymentApiTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_payments_for_a_student()
    {
        $student = User::factory()->create();

        // Seed via API
        $this->postJson("/api/students/{$student->id}/payments", [
            'amount' => 50,
            'method' => 'cash',
        ]);

        $this->getJson("/api/students/{$student->id}/payments")
            ->assertStatus(200)
            ->assertJsonFragment(['amount' => 50]);
    }

    /** @test */
    public function it_creates_a_student_payment()
    {
        $student = User::factory()->create();

        $payload = [
            'amount' => 75,
            'method' => 'credit_card',
        ];

        $this->postJson("/api/students/{$student->id}/payments", $payload)
            ->assertStatus(201)
            ->assertJsonFragment($payload);
    }

    /** @test */
    public function it_shows_a_single_student_payment()
    {
        $student = User::factory()->create();

        $response = $this->postJson("/api/students/{$student->id}/payments", [
            'amount' => 30,
            'method' => 'paypal',
        ])->json();

        $paymentId = $response['id'] ?? null;

        $this->getJson("/api/students/{$student->id}/payments/{$paymentId}")
            ->assertStatus(200)
            ->assertJsonFragment(['amount' => 30]);
    }

    /** @test */
    public function it_updates_a_student_payment()
    {
        $student = User::factory()->create();

        $response = $this->postJson("/api/students/{$student->id}/payments", [
            'amount' => 100,
            'method' => 'cash',
        ])->json();

        $paymentId = $response['id'] ?? null;

        $this->patchJson("/api/students/{$student->id}/payments/{$paymentId}", [
            'amount' => 120,
        ])->assertStatus(200)
          ->assertJsonFragment(['amount' => 120]);
    }

    /** @test */
    public function it_deletes_a_student_payment()
    {
        $student = User::factory()->create();

        $response = $this->postJson("/api/students/{$student->id}/payments", [
            'amount' => 60,
            'method' => 'bank_transfer',
        ])->json();

        $paymentId = $response['id'] ?? null;

        $this->deleteJson("/api/students/{$student->id}/payments/{$paymentId}")
            ->assertStatus(200)
            ->assertJson(['message' => 'Payment deleted']);
    }
}
