<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_bearer_token_for_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $response = $this->postJson('/api/tokens', [
            'email' => $user->email,
            'password' => 'correct-password',
            'device_name' => 'Postman',
        ]);

        $response->assertCreated()->assertJsonStructure(['token']);
        $this->withToken($response->json('token'))
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Postman',
        ]);
    }

    public function test_returns_422_when_credentials_are_invalid(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/tokens', [
            'email' => $user->email,
            'password' => 'wrong-password',
            'device_name' => 'Postman',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
