<?php
uses(Illuminate\Foundation\Testing\RefreshDatabase::class);
beforeEach(function () {
    $this->seed(\Database\Seeders\BookSeeder::class);
});
use App\Models\User;
use Laravel\Sanctum\Sanctum;



test('puede listar un libro', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $firstSeededBook = \App\Models\Book::query()->first();
    $response = $this->getJson('api/v1/books');

    $response->assertStatus(200)
        ->assertJsonCount(15)
        ->assertJsonFragment([
            'title' => $firstSeededBook->title,
        ]);
});

test('Puede crear un libro', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

$response = $this->postJson('api/v1/books', [
        'title' => 'Test Book',
        'description' => 'This is a test book.',
        'ISBN' => '1234567890',
        'total_copies' => 5,
        'available_copies' => 5
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment([
            'title' => 'Test Book',
            'description' => 'This is a test book.',
            'ISBN' => '1234567890',
            'total_copies' => 5,
            'available_copies' => 5
        ]);

    $this->assertDatabaseHas('books', [
        'title' => 'Test Book',
        'description' => 'This is a test book.',
        'ISBN' => '1234567890',
        'total_copies' => 5,
        'available_copies' => 5
    ]);
});
 
test("Puede mostrar un libro", function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $book = \App\Models\Book::factory()->create();

    $response = $this->getJson("api/v1/books/{$book->id}");

    $response->assertStatus(200)
        ->assertJsonFragment([
            'title' => $book->title,
            'description' => $book->description,
            'ISBN' => $book->ISBN,
            'total_copies' => $book->total_copies,
        ]);
});

test("Puede actualizar un libro", function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $book = \App\Models\Book::factory()->create();

    $response = $this->putJson("api/v1/books/{$book->id}", [
        'title' => 'Updated Title',
        'description' => 'Updated description.',
        'ISBN' => '0987654321',
        'total_copies' => 10,
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment([
            'title' => 'Updated Title',
            'description' => 'Updated description.',
            'ISBN' => '0987654321',
            'total_copies' => 10,
        ]);

    $this->assertDatabaseHas('books', [
        'id' => $book->id,
        'title' => 'Updated Title',
        'description' => 'Updated description.',
        'ISBN' => '0987654321',
        'total_copies' => 10,
    ]);
});

test("Puede eliminar un libro", function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $book = \App\Models\Book::factory()->create();

    $response = $this->deleteJson("api/v1/books/{$book->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('books', [
        'id' => $book->id,
    ]);
});
