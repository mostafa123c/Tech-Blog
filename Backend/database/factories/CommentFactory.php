<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * Realistic comment templates
     */
    protected static array $commentTemplates = [
        "Great article! This really helped me understand %s better.",
        "Thanks for sharing this. I've been struggling with %s and this cleared things up.",
        "Interesting perspective. I would add that %s is also important to consider.",
        "This is exactly what I was looking for. Bookmarked for future reference!",
        "Well written! I especially liked the part about %s.",
        "I've been working with this for years and still learned something new.",
        "Could you elaborate more on %s? I'm curious about the details.",
        "Fantastic guide! I shared this with my team.",
        "Very practical advice. I'm going to try implementing this today.",
        "This solved a problem I've been dealing with for weeks. Thank you!",
        "I have a different approach but I can see why this works well.",
        "Clear and concise. This is how tutorials should be written.",
        "Just what I needed for my current project. Perfect timing!",
        "I appreciate the real-world examples. Makes it much easier to understand.",
        "Following this helped me complete my task successfully. Thanks!",
    ];

    protected static array $topics = [
        'the implementation details',
        'best practices',
        'performance optimization',
        'error handling',
        'testing strategies',
        'code organization',
        'scalability concerns',
        'security considerations',
    ];

    public function definition(): array
    {
        $template = fake()->randomElement(static::$commentTemplates);
        $topic = fake()->randomElement(static::$topics);

        $body = sprintf($template, $topic);

        return [
            'body'    => $body,
            'post_id' => Post::inRandomOrder()->first()?->id ?? Post::factory(),
            'user_id' => User::inRandomOrder()->first()?->id ?? User::factory(),
        ];
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn(array $attributes) => [
            'post_id' => $post->id,
        ]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
