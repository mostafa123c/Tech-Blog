<?php

namespace Tests\Feature;

use App\Jobs\DeleteExpiredPostsJob;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpiredPostsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function delete_expired_posts_job_removes_expired_posts(): void
    {
        $expiredPost1 = Post::factory()->expired()->create();
        $expiredPost1->tags()->attach(Tag::factory()->create());

        $expiredPost2 = Post::factory()->expired()->create();
        $expiredPost2->tags()->attach(Tag::factory()->create());

        $activePost = Post::factory()->create();
        $activePost->tags()->attach(Tag::factory()->create());

        $job = new DeleteExpiredPostsJob();
        $job->handle();

        $this->assertSoftDeleted('posts', ['id' => $expiredPost1->id]);
        $this->assertSoftDeleted('posts', ['id' => $expiredPost2->id]);

        $this->assertDatabaseHas('posts', [
            'id' => $activePost->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function job_handles_no_expired_posts_gracefully(): void
    {
        $activePost = Post::factory()->create();
        $activePost->tags()->attach(Tag::factory()->create());

        $job = new DeleteExpiredPostsJob();
        $job->handle();

        $this->assertDatabaseHas('posts', [
            'id' => $activePost->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function job_handles_empty_database_gracefully(): void
    {
        $this->assertDatabaseCount('posts', 0);

        $job = new DeleteExpiredPostsJob();
        $job->handle();

        $this->assertDatabaseCount('posts', 0);
    }

    #[Test]
    public function expired_posts_not_shown_in_listing(): void
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $expiredPost = Post::factory()->expired()->create();
        $expiredPost->tags()->attach(Tag::factory()->create());

        $activePost = Post::factory()->create();
        $activePost->tags()->attach(Tag::factory()->create());

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/posts');

        $response->assertStatus(200);

        $items = collect($response->json('data.items'));

        $this->assertEquals(1, $items->count());
        $this->assertEquals($activePost->id, $items->first()['id']);
    }

    #[Test]
    public function post_just_about_to_expire_is_still_visible(): void
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $almostExpiredPost = Post::factory()->create([
            'expires_at' => now()->addMinute(),
        ]);
        $almostExpiredPost->tags()->attach(Tag::factory()->create());

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/posts');

        $response->assertStatus(200);

        $items = collect($response->json('data.items'));
        $this->assertContains($almostExpiredPost->id, $items->pluck('id'));
    }

    #[Test]
    public function new_posts_have_24_hour_expiry(): void
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/posts', [
            'title' => 'Test Post',
            'body' => 'Test body',
            'tags' => ['test'],
        ]);

        $response->assertStatus(201);

        $post = Post::find($response->json('data.id'));

        $hoursUntilExpiry = now()->diffInHours($post->expires_at);
        $this->assertGreaterThanOrEqual(23, $hoursUntilExpiry);
        $this->assertLessThanOrEqual(24, $hoursUntilExpiry);
    }

    #[Test]
    public function expires_at_is_included_in_post_response(): void
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $post = Post::factory()->create(['user_id' => $user->id]);
        $post->tags()->attach(Tag::factory()->create());

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200)->assertJsonStructure([
            'data' => [
                'expires_at',
            ],
        ]);
    }
}
