<?php

namespace Tests\Feature\Http\Controllers\Api;

use Tests\TestCase;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_store()
    {
        // $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'api')->json('POST', '/api/posts', [
            'title' => 'El post de prueba'
        ]);

        $response->assertJsonStructure(['id', 'title', 'created_at', 'updated_at'])
            ->assertJson(['title' => 'El post de prueba'])
            ->assertStatus(201); // OK, se creo un recurso

        $this->assertDatabaseHas('posts', ['title' => 'El post de prueba']);
    }

    public function test_validate_title()
    {
        // $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'api')->json('POST', '/api/posts', [
            'title' => ''
        ]);

        $response->assertStatus(422) // Solicitud bien hecha, pero imposible completarla.
            ->assertJsonValidationErrors('title');
    }

    public function test_show()
    {
        // $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user, 'api')->json('GET', "/api/posts/$post->id");

        $response->assertJsonStructure(['id', 'title', 'created_at', 'updated_at'])
            ->assertJson(['title' => $post->title])
            ->assertStatus(200);
    }

    public function test_404_show()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'api')->json('GET', "/api/posts/1000");
        $response->assertStatus(404);
    }

    public function test_update()
    {
        // $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user, 'api')->json('PUT', "/api/posts/$post->id", [
            'title' => 'Nuevo titulo'
        ]);

        $response->assertJsonStructure(['id', 'title', 'created_at', 'updated_at'])
            ->assertJson(['title' => 'Nuevo titulo'])
            ->assertStatus(200); // OK

        $this->assertDatabaseHas('posts', ['title' => 'Nuevo titulo']);
    }

    public function test_delete()
    {
        // $this->withoutExceptionHandling();
        $user = User::factory()->create();
        // Paso 1: Crear el post
        $post = Post::factory()->create();

        // Paso 2: Eliminar el post
        $response = $this->actingAs($user, 'api')->json("DELETE", "/api/posts/$post->id");

        // Paso 3: Verificar que no se retorne nada con estatus 204
        $response->assertSee(null)->assertStatus(204); // Sin contenido...

        // Paso 4: Verificara que no exista el post eliminado
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_index()
    {
        $user = User::factory()->create();

        // Paso 1: Crear los posts
        Post::factory(5)->create();

        // Paso 2: Obtener los posts
        $response = $this->actingAs($user, 'api')->json("GET", "/api/posts");

        // Paso 3: Verificar que estamos obteniendo la estructura Json deseada
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'created_at', 'updated_at']
            ]
        ])->assertStatus(200); // OK
    }

    public function test_guest()
    {
        $this->json('GET', '/api/posts')->assertStatus(401);
        $this->json('GET', '/api/posts/10')->assertStatus(401);
        $this->json('PUT', '/api/posts/10')->assertStatus(401);
        $this->json('DELETE', '/api/posts/10')->assertStatus(401);
        $this->json('POST', '/api/posts')->assertStatus(401);
    }
}
