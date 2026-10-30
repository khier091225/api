<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_when_posts_are_requested_without_a_valid_token(): void
    {
        $this->getJson('/api/posts')->assertUnauthorized();

        $this->withToken('invalid-token')
            ->getJson('/api/posts')
            ->assertUnauthorized();
    }

    public function test_returns_paginated_posts_for_an_authenticated_user(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(11)->for($user, 'author')->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonStructure(['data' => [['id', 'title', 'body']], 'links', 'meta']);
    }

    public function test_creates_a_post_and_returns_201_for_valid_input(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [
            'title' => 'RESTful APIs',
            'body' => 'Build an API with Laravel.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'RESTful APIs');

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'title' => 'RESTful APIs',
            'body' => 'Build an API with Laravel.',
        ]);
    }

    public function test_returns_422_when_post_fields_are_missing(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/posts', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'body']);
    }

    public function test_updates_an_owned_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        Sanctum::actingAs($user);

        $this->putJson("/api/posts/{$post->id}", ['title' => 'Updated title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated title');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated title',
        ]);
    }

    public function test_returns_403_when_a_user_updates_another_users_post(): void
    {
        $post = Post::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->putJson("/api/posts/{$post->id}", ['title' => 'Not allowed'])
            ->assertForbidden();

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
            'title' => 'Not allowed',
        ]);
    }

    public function test_deletes_an_owned_post_and_returns_204(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user, 'author')->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/posts/{$post->id}")->assertNoContent();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
