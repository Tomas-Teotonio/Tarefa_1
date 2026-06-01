<?php

use App\Models\Book;
use App\Models\Publisher;
use App\Models\Request as BookRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function createCitizen(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'name' => 'Cidadão Teste',
        'email' => uniqid('citizen_') . '@test.com',
        'email_verified_at' => now(),
        'role' => 'citizen',
    ], $attributes));
}

function createAdmin(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'name' => 'Admin Teste',
        'email' => uniqid('admin_') . '@test.com',
        'email_verified_at' => now(),
        'role' => 'admin',
    ], $attributes));
}

function createTestBook(array $attributes = []): Book
{
    $publisher = Publisher::create([
        'name' => 'Editora Teste ' . uniqid(),
    ]);

    return Book::create(array_merge([
        'isbn' => 'ISBN-' . uniqid(),
        'name' => 'Livro Teste',
        'publisher_id' => $publisher->id,
        'bibliography' => 'Descrição de teste do livro.',
        'cover_image' => null,
        'price' => 10.00,
        'stock' => 1,
    ], $attributes));
}

it('permite criar uma requisição de livro corretamente', function () {
    Mail::fake();

    $user = createCitizen();
    $book = createTestBook();

    $response = $this
        ->actingAs($user)
        ->post(route('books.request', $book));

    $response->assertRedirect();

    $this->assertDatabaseHas('requests', [
        'user_id' => $user->id,
        'book_id' => $book->id,
        'status' => 'active',
    ]);

    $request = BookRequest::where('user_id', $user->id)
        ->where('book_id', $book->id)
        ->first();

    expect($request)->not()->toBeNull();
    expect($request->number)->toStartWith('REQ-');
    expect($request->expected_return_date)->not()->toBeNull();
});

it('não permite criar requisição sem livro válido', function () {
    $user = createCitizen();

    $response = $this
        ->actingAs($user)
        ->post('/books/999999/request');

    $response->assertNotFound();

    expect(BookRequest::count())->toBe(0);
});

it('permite ao admin devolver um livro', function () {
    Mail::fake();
    Storage::fake('public');

    $admin = createAdmin();
    $citizen = createCitizen();
    $book = createTestBook();

    $bookRequest = BookRequest::create([
        'number' => 'REQ-TESTE',
        'user_id' => $citizen->id,
        'book_id' => $book->id,
        'request_date' => now()->subDays(2),
        'expected_return_date' => now()->addDays(3),
        'status' => 'active',
    ]);

    $photo = UploadedFile::fake()->image('proof.jpg');

    $response = $this
        ->actingAs($admin)
        ->post(route('requests.return', $bookRequest), [
            'photo' => $photo,
        ]);

    $response->assertRedirect();

    $bookRequest->refresh();

    expect($bookRequest->status)->toBe('returned');
    expect($bookRequest->actual_return_date)->not()->toBeNull();
    expect((int) $bookRequest->days_used)->toBeGreaterThanOrEqual(0);
    expect($bookRequest->user_photo)->not()->toBeNull();
});

it('lista apenas as requisições do utilizador autenticado', function () {
    $this->withoutVite();

    $userA = createCitizen();
    $userB = createCitizen();

    $bookA = createTestBook([
        'isbn' => 'ISBN-A-' . uniqid(),
        'name' => 'Livro A',
    ]);

    $bookB = createTestBook([
        'isbn' => 'ISBN-B-' . uniqid(),
        'name' => 'Livro B',
    ]);

    BookRequest::create([
        'number' => 'REQ-A',
        'user_id' => $userA->id,
        'book_id' => $bookA->id,
        'request_date' => now(),
        'expected_return_date' => now()->addDays(5),
        'status' => 'active',
    ]);

    BookRequest::create([
        'number' => 'REQ-B',
        'user_id' => $userB->id,
        'book_id' => $bookB->id,
        'request_date' => now(),
        'expected_return_date' => now()->addDays(5),
        'status' => 'active',
    ]);

    $response = $this
        ->actingAs($userA)
        ->get(route('requests.index'));

    $response->assertOk();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Requests/Index')
        ->has('requests', 1)
        ->where('requests.0.user_id', $userA->id)
        ->where('requests.0.book_id', $bookA->id)
    );
});

it('não permite requisitar livro sem stock disponível', function () {
    Mail::fake();

    $user = createCitizen();

    $book = createTestBook([
        'stock' => 0,
    ]);

    $response = $this
        ->actingAs($user)
        ->post(route('books.request', $book));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Livro indisponível.');

    $this->assertDatabaseMissing('requests', [
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]);
});