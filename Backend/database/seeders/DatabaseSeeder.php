<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Database\Factories\TagFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        TagFactory::resetIndex();

        $this->command->info('Creating users...');
        $users = User::factory(10)->create();

        $demoUser = User::factory()->create([
            'name' => 'Mostafa Emad',
            'email' => 'mostafa@gmail.com',
        ]);
        $users->push($demoUser);

        $this->command->info('Creating tags...');
        $tags = Tag::factory(30)->create();

        $this->command->info('Creating posts with tags...');
        $posts = collect();

        foreach ($users as $user) {
            $userPosts = Post::factory(rand(10, 50))->withUser($user)->create();

            foreach ($userPosts as $post) {
                $randomTags = $tags->random(rand(1, 4));
                $post->tags()->attach($randomTags->pluck('id'));
            }

            $posts = $posts->merge($userPosts);
        }

        $this->command->info('Creating comments...');
        foreach ($posts as $post) {
            $commentCount = rand(0, 8);

            for ($i = 0; $i < $commentCount; $i++) {
                Comment::factory()->forPost($post)->byUser($users->random())->create();
            }
        }

        $this->command->info('Seeding completed!');
        $this->command->info("Created: {$users->count()} users, {$tags->count()} tags, {$posts->count()} posts");
    }
}
