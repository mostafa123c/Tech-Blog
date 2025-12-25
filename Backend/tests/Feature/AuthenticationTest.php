<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'image' => UploadedFile::fake()->image('avatar.jpg', 100, 100),
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'email'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);
    }

    #[Test]
    public function registration_fails_with_missing_name(): void
    {
        $response = $this->postJson('/api/register', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function registration_fails_with_missing_email(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function registration_fails_with_invalid_email_format(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function registration_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function registration_fails_with_missing_password(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function registration_fails_with_invalid_image_type(): void
    {
        Storage::fake('public');

        $response = $this->postJson('/api/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'image' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['image']);
    }

    #[Test]
    public function user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'expires_in',
                'user' => ['id', 'name', 'email'],
            ]);
    }

    #[Test]
    public function login_fails_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)->assertJson([
            'success' => false,
            'message' => 'Invalid credentials',
        ]);
    }

    #[Test]
    public function login_fails_with_non_existent_email(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)->assertJson([
            'success' => false,
            'message' => 'Invalid credentials',
        ]);
    }

    #[Test]
    public function login_fails_with_missing_email(): void
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function login_fails_with_missing_password(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function login_fails_with_invalid_email_format(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/logout');

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    #[Test]
    public function authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }
    #[Test]
    public function protected_routes_require_authentication(): void
    {
        $protectedRoutes = [
            ['method' => 'get', 'url' => '/api/posts'],
            ['method' => 'post', 'url' => '/api/posts'],
            ['method' => 'get', 'url' => '/api/posts/1'],
            ['method' => 'put', 'url' => '/api/posts/1'],
            ['method' => 'delete', 'url' => '/api/posts/1'],
            ['method' => 'get', 'url' => '/api/posts/1/comments'],
            ['method' => 'post', 'url' => '/api/posts/1/comments'],
            ['method' => 'put', 'url' => '/api/comments/1'],
            ['method' => 'delete', 'url' => '/api/comments/1'],
            ['method' => 'get', 'url' => '/api/me'],
            ['method' => 'post', 'url' => '/api/logout'],
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->{$route['method'] . 'Json'}($route['url']);
            $response->assertStatus(401, "Route {$route['method']} {$route['url']} should be protected");
        }
    }

    #[Test]
    public function public_routes_do_not_require_authentication(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password123',
            'image' => UploadedFile::fake()->image('avatar.jpg', 100, 100)
        ]);
        $this->assertNotEquals(401, $response->status());

        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
            'password' => 'password123'
        ]);
        $this->assertNotEquals(401, $response->status());
    }
}
