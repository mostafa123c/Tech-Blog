<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Policies\CommentPolicy;
use App\Policies\PostPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function post_owner_can_update_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $policy = new PostPolicy();

        $this->assertTrue($policy->update($user, $post));
    }

    #[Test]
    public function non_owner_cannot_update_post(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $policy = new PostPolicy();

        $this->assertFalse($policy->update($otherUser, $post));
    }

    #[Test]
    public function post_owner_can_delete_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $policy = new PostPolicy();

        $this->assertTrue($policy->delete($user, $post));
    }

    #[Test]
    public function non_owner_cannot_delete_post(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $owner->id]);

        $policy = new PostPolicy();

        $this->assertFalse($policy->delete($otherUser, $post));
    }

    #[Test]
    public function comment_owner_can_update_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $policy = new CommentPolicy();

        $this->assertTrue($policy->update($user, $comment));
    }

    #[Test]
    public function non_owner_cannot_update_comment(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $owner->id,
            'post_id' => $post->id,
        ]);

        $policy = new CommentPolicy();

        $this->assertFalse($policy->update($otherUser, $comment));
    }

    #[Test]
    public function comment_owner_can_delete_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $policy = new CommentPolicy();

        $this->assertTrue($policy->delete($user, $comment));
    }

    #[Test]
    public function non_owner_cannot_delete_comment(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $owner->id,
            'post_id' => $post->id,
        ]);

        $policy = new CommentPolicy();

        $this->assertFalse($policy->delete($otherUser, $comment));
    }

    #[Test]
    public function post_author_cannot_manage_others_comments(): void
    {
        $postOwner = User::factory()->create();
        $commenter = User::factory()->create();

        $post = Post::factory()->create(['user_id' => $postOwner->id]);
        $comment = Comment::factory()->create([
            'user_id' => $commenter->id,
            'post_id' => $post->id,
        ]);

        $policy = new CommentPolicy();

        $this->assertFalse($policy->update($postOwner, $comment));
        $this->assertFalse($policy->delete($postOwner, $comment));
    }
}
