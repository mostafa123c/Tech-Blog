<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * Realistic blog post titles
     */
    protected static array $titleTemplates = [
        'How to %s in %d Easy Steps',
        'The Complete Guide to %s',
        'Why %s Matters More Than Ever',
        '%s: A Comprehensive Overview',
        'Top %d Tips for Better %s',
        'Understanding %s: What You Need to Know',
        'The Future of %s in %d',
        '%s Best Practices for Beginners',
        'How I Improved My %s in %d Days',
        'The Ultimate %s Tutorial',
    ];

    protected static array $topics = [
        'Web Development',
        'React',
        'Laravel',
        'JavaScript',
        'PHP',
        'API Design',
        'Database Optimization',
        'Code Quality',
        'Testing',
        'Performance',
        'Security',
        'DevOps',
        'Cloud Computing',
        'AI',
        'Machine Learning',
        'User Experience',
        'Mobile Development',
        'Frontend Development',
        'Backend Development',
        'Full Stack',
    ];

    public function definition(): array
    {
        $template = fake()->randomElement(static::$titleTemplates);
        $topic = fake()->randomElement(static::$topics);
        $number = fake()->numberBetween(3, 15);
        $year = fake()->numberBetween(2024, 2025);

        $title = sprintf($template, $topic, $number ?: $year);

        return [
            'title'      => $title,
            'body'       => $this->generateRealisticBody($topic),
            'user_id'    => User::inRandomOrder()->first()?->id ?? User::factory(),
            'expires_at' => now()->addHours(fake()->numberBetween(1, 24)),
        ];
    }

    protected function generateRealisticBody(string $topic): string
    {
        $paragraphs = [];

        $paragraphs[] = "In today's rapidly evolving tech landscape, understanding {$topic} has become essential for developers and professionals alike. This article explores the key concepts and practical applications that will help you master this important subject.";

        $paragraphs[] = fake()->paragraphs(2, true);

        $paragraphs[] = "When working with {$topic}, there are several important considerations to keep in mind. First, always focus on code quality and maintainability. Second, ensure proper testing and documentation. Third, stay updated with the latest best practices and industry standards.";

        $paragraphs[] = fake()->paragraphs(2, true);

        $paragraphs[] = "By following these guidelines and continuously learning, you'll be well on your way to becoming proficient in {$topic}. Remember, the key to success is consistent practice and staying curious about new developments in the field.";

        return implode("\n\n", $paragraphs);
    }

    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'expires_at' => now()->subHour(),
        ]);
    }

    public function withUser(User $user): static
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
