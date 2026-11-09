<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_endpoints_require_a_valid_token(): void
    {
        $this->getJson('/api/books')->assertUnauthorized();
        $this->postJson('/api/books', [])->assertUnauthorized();
    }

    public function test_books_can_be_listed_searched_and_paginated(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        Book::factory()->create(['title' => 'Laravel untuk Pemula', 'author' => 'Ayu']);
        Book::factory()->create(['title' => 'Belajar API', 'author' => 'Budi']);

        $this->getJson('/api/books?search=Laravel&per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Laravel untuk Pemula')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/books?per_page=1000')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_a_book_can_be_created_and_duplicate_isbn_is_rejected(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $payload = [
            'title' => 'Belajar Laravel',
            'author' => 'Edwin',
            'publisher' => 'Penerbit Kampus',
            'isbn' => '9781234567890',
            'published_year' => (int) date('Y'),
            'stock' => 5,
            'description' => 'Panduan REST API.',
        ];

        $this->postJson('/api/books', $payload)
            ->assertCreated()
            ->assertJsonPath('data.isbn', $payload['isbn']);

        $this->postJson('/api/books', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('isbn');

        $this->assertDatabaseCount('books', 1);
    }

    public function test_a_book_can_be_shown_and_updated_with_put_or_patch(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $book = Book::factory()->create(['title' => 'Judul Lama']);

        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Judul Lama');

        $this->patchJson("/api/books/{$book->id}", ['title' => 'Judul Baru'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Judul Baru');

        $book->refresh();
        $this->putJson("/api/books/{$book->id}", [
            'title' => 'Judul Final',
            'author' => $book->author,
            'publisher' => $book->publisher,
            'isbn' => $book->isbn,
            'published_year' => $book->published_year,
            'stock' => $book->stock,
            'description' => $book->description,
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Judul Final');
    }

    public function test_a_book_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum');
        $book = Book::factory()->create();

        $this->deleteJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Buku berhasil dihapus');

        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->getJson("/api/books/{$book->id}")->assertNotFound();
    }
}
