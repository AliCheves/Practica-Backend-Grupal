<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Models\User;
use App\Models\Book;
use App\Models\Loan;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Docente', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Estudiante', 'guard_name' => 'web']);
});

// PRESTAR
test('puede prestar un libro', function () {
    $user = User::factory()->create();
    $user->assignRole('Docente');
    Sanctum::actingAs($user);
    $book = Book::factory()->create(['available_copies' => 5, 'is_available' => true]);

    $response = $this->postJson('api/v1/loans', [
        'requester_name' => 'John Doe',
        'book_id' => $book->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment([
        'requester_name' => 'John Doe',
        'book_id' => $book->id,
    ]);

    $this->assertDatabaseHas('loans', [
        'requester_name' => 'John Doe',
        'book_id' => $book->id,
    ]);

    $this->assertDatabaseHas('books', [
        'id' => $book->id,
        'available_copies' => 4,
    ]);
});

test('no puede prestar un libro sin copias disponibles', function () {
    $user = User::factory()->create();
    $user->assignRole('Docente');
    Sanctum::actingAs($user);
    $book = Book::factory()->create(['available_copies' => 0, 'is_available' => false]);

    $response = $this->postJson('api/v1/loans', [
        'requester_name' => 'John Doe',
        'book_id' => $book->id,
    ]);

    $response->assertStatus(422)
        ->assertJson(['message' => 'Book is not available']);
});

test('valida los datos al prestar un libro', function () {
    $user = User::factory()->create();
    $user->assignRole('Docente');
    Sanctum::actingAs($user);

    $response = $this->postJson('api/v1/loans', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['requester_name', 'book_id']);
});

test('no autorizado para prestar sin autenticacion', function () {
    $book = Book::factory()->create(['available_copies' => 5, 'is_available' => true]);

    $response = $this->postJson('api/v1/loans', [
        'requester_name' => 'John Doe',
        'book_id' => $book->id,
    ]);

    $response->assertStatus(401);
});

// DEVOLVER
test('puede devolver un préstamo activo', function () {
    $user = User::factory()->create();
    $user->assignRole('Docente');
    Sanctum::actingAs($user);
    $book = Book::factory()->create(['available_copies' => 4, 'is_available' => true]);
    $loan = Loan::create([
        'requester_name' => 'Jane Doe',
        'book_id' => $book->id,
    ]);

    $response = $this->postJson("api/v1/loans/{$loan->id}/return");

    $response->assertStatus(200);

    $this->assertDatabaseHas('loans', [
        'id' => $loan->id,
    ]);

    $loan->refresh();
    $this->assertNotNull($loan->return_at);

    $this->assertDatabaseHas('books', [
        'id' => $book->id,
        'available_copies' => 5,
        'is_available' => true,
    ]);
});

test('no puede devolver un préstamo ya devuelto', function () {
    $user = User::factory()->create();
    $user->assignRole('Docente');
    Sanctum::actingAs($user);
    $book = Book::factory()->create();
    $loan = Loan::create([
        'requester_name' => 'Jane Doe',
        'book_id' => $book->id,
        'return_at' => now(),
    ]);

    $response = $this->postJson("api/v1/loans/{$loan->id}/return");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Loan already returned']);
});

test('error al devolver préstamo inexistente', function () {
    $user = User::factory()->create();
    $user->assignRole('Docente');
    Sanctum::actingAs($user);

    $response = $this->postJson('api/v1/loans/999/return');

    $response->assertStatus(404);
});

test('no autorizado para devolver sin autenticacion', function () {
    $book = Book::factory()->create();
    $loan = Loan::create([
        'requester_name' => 'Jane Doe',
        'book_id' => $book->id,
    ]);

    $response = $this->postJson("api/v1/loans/{$loan->id}/return");

    $response->assertStatus(401);
});

// HISTORIAL
test('puede obtener el historial de préstamos', function () {
    $user = User::factory()->create();
    $user->assignRole('Docente');
    Sanctum::actingAs($user);

    $book = Book::factory()->create();
    Loan::create([
        'requester_name' => 'John Doe',
        'book_id' => $book->id,
    ]);
    Loan::create([
        'requester_name' => 'Jane Doe',
        'book_id' => $book->id,
    ]);

    $response = $this->getJson('api/v1/loans');

    $response->assertStatus(200);
});

test('obtiene historial vacío si no hay préstamos', function () {
    $user = User::factory()->create();
    $user->assignRole('Docente');
    Sanctum::actingAs($user);

    $response = $this->getJson('api/v1/loans');

    $response->assertStatus(200);
});

test('no autorizado para ver historial sin autenticacion', function () {
    $response = $this->getJson('api/v1/loans');

    $response->assertStatus(401);
});
