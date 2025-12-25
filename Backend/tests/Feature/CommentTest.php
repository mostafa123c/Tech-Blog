<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = auth()->login($this->user);

        $this->post = Post::factory()->create();
        $this->post->tags()->attach(Tag::factory()->create());
    }

    private function authHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->token}"];
    }
    #[Test]
    public function user_can_list_comments_for_a_post(): void
    {
        Comment::factory()->count(3)->create([
            'post_id' => $this->post->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertStatus(200)->assertJson(['success' => true])->assertJsonStructure([
            'success',
            'data' => [
                'items',
                'total',
                'current',
            ],
        ]);

        $this->assertCount(3, $response->json('data.items'));
    }

    #[Test]
    public function comments_list_includes_user_info(): void
    {
        Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/posts/{$this->post->id}/comments");

        $response->assertStatus(200)->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'body',
                        'user' => ['id', 'name'],
                        'created_at',
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function listing_comments_for_non_existent_post_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/posts/99999/comments');

        $response->assertStatus(404);
    }

    #[Test]
    public function user_can_add_comment_to_any_post(): void
    {
        $otherUser = User::factory()->create();
        $otherPost = Post::factory()->create(['user_id' => $otherUser->id]);
        $otherPost->tags()->attach(Tag::factory()->create());

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/posts/{$otherPost->id}/comments", [
                'body' => 'This is my comment on someone else\'s post.',
            ]);

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'message' => 'Comment added successfully',
        ])->assertJsonStructure([
            'data' => [
                'id',
                'body',
                'user',
                'created_at',
            ],
        ]);

        $this->assertDatabaseHas('comments', [
            'body' => 'This is my comment on someone else\'s post.',
            'user_id' => $this->user->id,
            'post_id' => $otherPost->id,
        ]);
    }

    #[Test]
    public function user_can_comment_on_own_post(): void
    {
        $ownPost = Post::factory()->create(['user_id' => $this->user->id]);
        $ownPost->tags()->attach(Tag::factory()->create());

        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/posts/{$ownPost->id}/comments", [
                'body' => 'Commenting on my own post.',
            ]);

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'message' => 'Comment added successfully',
        ])->assertJsonStructure([
            'data' => [
                'id',
                'body',
                'user',
                'created_at',
            ],
        ]);
    }

    #[Test]
    public function create_comment_fails_without_body(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/posts/{$this->post->id}/comments", []);

        $response->assertStatus(422)->assertJsonValidationErrors(['body']);
    }

    #[Test]
    public function create_comment_fails_with_empty_body(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson("/api/posts/{$this->post->id}/comments", [
                'body' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    #[Test]
    public function create_comment_on_non_existent_post_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/posts/99999/comments', [
                'body' => 'Comment on non-existent post.',
            ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function unauthenticated_user_cannot_create_comment(): void
    {
        auth()->logout();

        $response = $this->postJson("/api/posts/{$this->post->id}/comments", [
            'body' => 'Trying to comment without auth.',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function owner_can_update_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'body' => 'Original comment',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/comments/{$comment->id}", [
                'body' => 'Updated comment body',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Comment updated successfully',
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'body' => 'Updated comment body',
        ]);
    }

    #[Test]
    public function non_owner_cannot_update_comment(): void
    {
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/comments/{$comment->id}", [
                'body' => 'Trying to update someone else\'s comment',
            ]);

        $this->assertTrue(in_array($response->status(), [400, 403]));
    }

    #[Test]
    public function update_comment_fails_without_body(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/comments/{$comment->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    #[Test]
    public function update_non_existent_comment_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->putJson('/api/comments/99999', [
                'body' => 'Updated comment',
            ]);

        $response->assertStatus(404);
    }

    #[Test]
    public function owner_can_delete_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Comment deleted successfully',
            ]);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    #[Test]
    public function non_owner_cannot_delete_comment(): void
    {
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/comments/{$comment->id}");

        $this->assertTrue(in_array($response->status(), [400, 403]));

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    #[Test]
    public function delete_non_existent_comment_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson('/api/comments/99999');

        $response->assertStatus(404);
    }

    #[Test]
    public function post_author_cannot_delete_others_comments(): void
    {
        $ownPost = Post::factory()->create(['user_id' => $this->user->id]);
        $ownPost->tags()->attach(Tag::factory()->create());
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $ownPost->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())->deleteJson("/api/comments/{$comment->id}");

        $this->assertTrue(in_array($response->status(), [400, 403]));
    }
}
