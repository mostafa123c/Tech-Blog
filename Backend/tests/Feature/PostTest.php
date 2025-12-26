<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }

    #[Test]
    public function user_can_list_all_non_expired_posts(): void
    {
        $activePosts = Post::factory()->count(3)->create();
        foreach ($activePosts as $post) {
            $post->tags()->attach(Tag::factory()->create());
        }

        $expiredPost = Post::factory()->expired()->create();
        $expiredPost->tags()->attach(Tag::factory()->create());

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'items',
                    'total',
                    'current',
                    'per',
                ],
            ]);

        $this->assertCount(3, $response->json('data.items'));
    }

    #[Test]
    public function posts_list_includes_user_and_tags(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $tag = Tag::factory()->create(['name' => 'technology']);
        $post->tags()->attach($tag);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/posts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'title',
                            'body',
                            'user' => ['id', 'name'],
                            'tags',
                            'expires_at',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                ],
            ]);
    }

    #[Test]
    public function user_can_create_post_with_valid_data(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/posts', [
                'title' => 'My First Post',
                'body' => 'This is the body of my first post.',
                'tags' => ['technology', 'programming'],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Post created successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'body',
                    'user',
                    'tags',
                    'expires_at',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'My First Post',
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseHas('tags', ['name' => 'technology']);
        $this->assertDatabaseHas('tags', ['name' => 'programming']);
    }

    #[Test]
    public function post_expires_after_24_hours(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/posts', [
                'title' => 'Test Post',
                'body' => 'Test body',
                'tags' => ['test'],
            ]);

        $response->assertStatus(201);

        $post = Post::find($response->json('data.id'));

        $this->assertTrue($post->expires_at->isFuture());
        $hoursDiff = abs($post->expires_at->floatDiffInHours(now()));
        $this->assertGreaterThanOrEqual(23.9, $hoursDiff);
        $this->assertLessThanOrEqual(24.1, $hoursDiff);
    }

    #[Test]
    public function create_post_fails_without_title(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/posts', [
                'body' => 'This is the body.',
                'tags' => ['test'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    #[Test]
    public function create_post_fails_without_body(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/posts', [
                'title' => 'My Post',
                'tags' => ['test'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    #[Test]
    public function create_post_fails_without_tags(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/posts', [
                'title' => 'My Post',
                'body' => 'Post body',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    }

    #[Test]
    public function create_post_fails_with_empty_tags_array(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/posts', [
                'title' => 'My Post',
                'body' => 'Post body',
                'tags' => [],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    }

    #[Test]
    public function unauthenticated_user_cannot_create_post(): void
    {
        auth()->logout();

        $response = $this->postJson('/api/posts', [
            'title' => 'My Post',
            'body' => 'Post body',
            'tags' => ['test'],
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function user_can_view_single_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $tag = Tag::factory()->create();
        $post->tags()->attach($tag);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $post->id,
                    'title' => $post->title,
                ],
            ]);
    }

    #[Test]
    public function view_non_existent_post_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/posts/99999');

        $response->assertStatus(404);
    }

    #[Test]
    public function owner_can_update_own_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $post->tags()->attach(Tag::factory()->create());

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/posts/{$post->id}", [
                'title' => 'Updated Title',
                'body' => 'Updated body content',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Post updated successfully',
            ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title',
            'body' => 'Updated body content',
        ]);
    }

    #[Test]
    public function owner_can_update_post_tags(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $oldTag = Tag::factory()->create(['name' => 'old-tag']);
        $post->tags()->attach($oldTag);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/posts/{$post->id}", [
                'tags' => ['new-tag-1', 'new-tag-2'],
            ]);

        $response->assertStatus(200);

        $post->refresh();
        $tagNames = $post->tags->pluck('name')->toArray();
        $this->assertContains('new-tag-1', $tagNames);
        $this->assertContains('new-tag-2', $tagNames);
        $this->assertNotContains('old-tag', $tagNames);
    }

    #[Test]
    public function non_owner_cannot_update_post(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $otherUser->id]);
        $post->tags()->attach(Tag::factory()->create());

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/posts/{$post->id}", [
                'title' => 'Hacked Title',
            ]);

        $this->assertTrue(in_array($response->status(), [400, 403]));
    }

    #[Test]
    public function update_non_existent_post_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/posts/99999', [
                'title' => 'Updated',
            ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function owner_can_delete_own_post(): void
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $post->tags()->attach(Tag::factory()->create());

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/posts/{$post->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Post deleted successfully',
            ]);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    #[Test]
    public function non_owner_cannot_delete_post(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $otherUser->id]);
        $post->tags()->attach(Tag::factory()->create());

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/posts/{$post->id}");

        $this->assertTrue(in_array($response->status(), [400, 403]));

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    #[Test]
    public function delete_non_existent_post_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson('/api/posts/99999');

        $response->assertStatus(404);
    }

    #[Test]
    public function existing_tags_are_reused_not_duplicated(): void
    {
        $this->withHeaders($this->authHeaders())
            ->postJson('/api/posts', [
                'title' => 'First Post',
                'body' => 'First body',
                'tags' => ['shared-tag'],
            ]);

        $this->withHeaders($this->authHeaders())
            ->postJson('/api/posts', [
                'title' => 'Second Post',
                'body' => 'Second body',
                'tags' => ['shared-tag'],
            ]);

        $this->assertEquals(1, Tag::where('name', 'shared-tag')->count());
    }

    #[Test]
    public function tags_are_normalized_to_lowercase(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/posts', [
                'title' => 'Test Post',
                'body' => 'Test body',
                'tags' => ['UPPERCASE', 'MixedCase'],
            ]);

        $response->assertStatus(201);

        $uppercaseTag = Tag::where('name', 'uppercase')->first();
        $mixedcaseTag = Tag::where('name', 'mixedcase')->first();

        $this->assertNotNull($uppercaseTag, 'Tag "uppercase" should exist in database');
        $this->assertNotNull($mixedcaseTag, 'Tag "mixedcase" should exist in database');
    }
}
