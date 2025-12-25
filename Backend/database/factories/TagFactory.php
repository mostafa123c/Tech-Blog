<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * Predefined realistic blog tags
     */
    protected static array $blogTags = [
        'technology',
        'programming',
        'javascript',
        'php',
        'laravel',
        'react',
        'web-development',
        'mobile',
        'design',
        'ui-ux',
        'startup',
        'business',
        'marketing',
        'productivity',
        'career',
        'health',
        'fitness',
        'lifestyle',
        'travel',
        'food',
        'science',
        'education',
        'tutorial',
        'news',
        'opinion',
        'review',
        'tips',
        'guide',
        'inspiration',
        'creativity',
        'photography',
        'music',
        'art',
        'books',
        'movies',
        'gaming',
        'sports',
        'politics',
        'environment',
        'finance',
    ];

    protected static int $tagIndex = 0;

    public function definition(): array
    {
        if (static::$tagIndex < count(static::$blogTags)) {
            $tagName = static::$blogTags[static::$tagIndex];
            static::$tagIndex++;
        } else {
            $tagName = fake()->word() . '-' . rand(100, 999);
        }

        return [
            'name' => $tagName,
        ];
    }

    public static function resetIndex(): void
    {
        static::$tagIndex = 0;
    }
}
