<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase; // Limpia la base de datos antes de cada prueba

    /**
     * Test it can login successfully
     */
    public function testItcanLoginSuccessfully()
    {
        // Crear un usuario de prueba
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        // Enviar la petición de login
        $response = $this->post('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que Laravel haya iniciado la sesión
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test it can return authenticated user information
     */
    public function testItCanReturnAuthenticatedUserInformation()
    {
        // Crear y autenticar un usuario
        $user = User::factory()->create();
        $this->actingAs($user);

        // Petición al endpoint protegido
        $response = $this->get('/api/v1/auth/profile');

        // Verificar respuesta exitosa y estructura de JSON
        $response->assertStatus(200)
            ->assertJson([
                'profile' => [
                    'email' => $user->email,
                ]
            ]);
    }

    /**
     * Test it can allow successfully logout
     */
    public function testItCanAllowSuccessfullyLogout()
    {
        // Crear y autenticar un usuario
        $user = User::factory()->create();

        // Enviar la petición de login
        $response = $this->post('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $token = $response->json('token');


        // Enviar la petición de logout
        $response = $this->withToken($token)->post('/api/v1/auth/logout');

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);

        // Verificar que el usuario ya no está autenticado
        $this->assertDatabaseEmpty('personal_access_tokens');
        $this->assertAuthenticated('sanctum');
    }


}
