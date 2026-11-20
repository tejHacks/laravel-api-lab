<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping(): void
    {
        $this->getJson('/api/v1/ping')->assertOk()->assertJson(['pong' => true]);
    }

    public function test_it_lists_books_with_pagination_shape(): void
    {
        Book::factory()->count(12)->create();

        $this->getJson('/api/v1/books?per_page=5')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'title', 'author', 'genre', 'is_classic', 'links']],
                'links' => ['first', 'next'],
                'meta' => ['current_page', 'total', 'per_page'],
            ])
            ->assertJsonPath('meta.total', 12);
    }

    public function test_search_filter_and_sort(): void
    {
        Book::factory()->create(['title' => 'Alpha', 'author' => 'Achebe', 'genre' => 'fiction', 'published_year' => 1958]);
        Book::factory()->create(['title' => 'Beta', 'author' => 'Okri', 'genre' => 'fiction', 'published_year' => 1991]);
        Book::factory()->create(['title' => 'Gamma', 'author' => 'Fowler', 'genre' => 'tech', 'published_year' => 1999]);

        $this->getJson('/api/v1/books?search=achebe')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Alpha');
        $this->getJson('/api/v1/books?genre=tech')->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Gamma');
        $this->getJson('/api/v1/books?sort=published_year')->assertJsonPath('data.0.title', 'Alpha');
        $this->getJson('/api/v1/books?sort=-published_year')->assertJsonPath('data.0.title', 'Gamma');
    }

    public function test_sorting_is_whitelisted(): void
    {
        $this->getJson('/api/v1/books?sort=password')->assertUnprocessable()->assertJsonValidationErrors('sort');
    }

    public function test_missing_book_returns_custom_json_404(): void
    {
        $this->getJson('/api/v1/books/9999')->assertNotFound()->assertJson(['code' => 'not_found']);
    }

    public function test_register_login_me_logout(): void
    {
        $token = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'password123', 'device_name' => 'test',
        ])->assertCreated()->json('token');

        $this->assertNotEmpty($token);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'ada@example.com', 'password' => 'password123', 'device_name' => 'phone',
        ])->assertOk()->assertJsonPath('abilities', ['books:read', 'books:write']);

        $this->withToken($login->json('token'))->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('user.email', 'ada@example.com');

        $this->withToken($login->json('token'))->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertDatabaseCount('personal_access_tokens', 1); // only the registration token remains
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::factory()->create(['email' => 'ada@example.com', 'password' => 'password']);

        $this->postJson('/api/v1/auth/login', ['email' => 'ada@example.com', 'password' => 'nope', 'device_name' => 'x'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_guests_cannot_write(): void
    {
        $this->postJson('/api/v1/books', ['title' => 'X'])->assertUnauthorized();
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_read_only_tokens_cannot_write(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['books:read']);

        $this->postJson('/api/v1/books', ['title' => 'X', 'author' => 'Y', 'genre' => 'tech'])->assertForbidden();
    }

    public function test_it_creates_and_validates_books(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['books:read', 'books:write']);

        $this->postJson('/api/v1/books', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'author', 'genre']);

        $this->postJson('/api/v1/books', ['title' => 'New', 'author' => 'Me', 'genre' => 'tech', 'published_year' => 2026])
            ->assertCreated()
            ->assertJsonPath('data.title', 'New')
            ->assertJsonPath('data.can_edit', true);

        $this->assertDatabaseHas('books', ['title' => 'New', 'user_id' => $user->id]);
    }

    public function test_only_the_owner_can_update_or_delete(): void
    {
        $owner = User::factory()->create();
        $book = Book::factory()->for($owner, 'owner')->create(['title' => 'Mine']);

        Sanctum::actingAs(User::factory()->create(), ['books:write']);
        $this->patchJson("/api/v1/books/{$book->id}", ['title' => 'Hacked'])->assertForbidden();
        $this->deleteJson("/api/v1/books/{$book->id}")->assertForbidden();

        Sanctum::actingAs($owner, ['books:write']);
        $this->patchJson("/api/v1/books/{$book->id}", ['title' => 'Renamed'])
            ->assertOk()->assertJsonPath('data.title', 'Renamed')->assertJsonPath('data.author', $book->author);
        $this->deleteJson("/api/v1/books/{$book->id}")->assertNoContent();
        $this->assertModelMissing($book);
    }

    public function test_v2_has_a_different_shape(): void
    {
        Book::factory()->create(['author' => 'Achebe', 'published_year' => 1958]);

        $this->getJson('/api/v2/books')
            ->assertOk()
            ->assertJsonPath('data.0.author.name', 'Achebe')
            ->assertJsonPath('data.0.year', 1958)
            ->assertJsonStructure(['meta' => ['next_cursor']]);
    }

    public function test_stats_endpoint(): void
    {
        Book::factory()->count(3)->create(['genre' => 'tech']);

        $this->getJson('/api/v1/stats')->assertOk()->assertJsonPath('data.books', 3)->assertJsonPath('data.by_genre.tech', 3);
    }

    public function test_auth_endpoints_are_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'x', 'device_name' => 'x']);
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'x@example.com', 'password' => 'x', 'device_name' => 'x'])
            ->assertTooManyRequests();
    }

    public function test_playground_page_loads(): void
    {
        $this->get('/playground')->assertOk()->assertSee('API Playground');
    }
}
