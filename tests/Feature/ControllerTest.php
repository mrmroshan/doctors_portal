<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use App\Services\OdooApi;

class ControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticate_user_success()
    {
        // Mock the OdooApi class
        $this->mock(OdooApi::class, function ($mock) {
            $mock->shouldReceive('authenticate')->andReturn(1); // Simulate successful authentication
        });

        $response = $this->postJson('/authenticate', [
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Authentication successful', 'uid' => 1]);
    }

    public function test_authenticate_user_failure()
    {
        // Mock the OdooApi class
        $this->mock(OdooApi::class, function ($mock) {
            $mock->shouldReceive('authenticate')->andReturn(null); // Simulate failed authentication
        });

        $response = $this->postJson('/authenticate', [
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Authentication failed']);
    }

    public function test_fetch_users_success()
    {
        // Mock the OdooApi class
        $this->mock(OdooApi::class, function ($mock) {
            $mock->shouldReceive('read')->andReturn([
                ['id' => 1, 'name' => 'User One', 'login' => 'userone@example.com'],
                ['id' => 2, 'name' => 'User Two', 'login' => 'usertwo@example.com'],
            ]);
        });

        $response = $this->getJson('/fetch-users', [
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $response->assertStatus(200)
                 ->assertViewIs('users.index')
                 ->assertViewHas('users');
    }

    public function test_fetch_users_failure()
    {
        // Mock the OdooApi class
        $this->mock(OdooApi::class, function ($mock) {
            $mock->shouldReceive('read')->andThrow(new \Exception('Error fetching users'));
        });

        $response = $this->getJson('/fetch-users', [
            'username' => 'testuser',
            'password' => 'testpassword',
        ]);

        $response->assertStatus(500)
                 ->assertJson(['message' => 'An error occurred: Error fetching users']);
    }
}
