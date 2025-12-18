<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_201()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test Register',
            'email' => 'register@test.com',
            'password' => 'password123456',
            'password_confirmation' => 'password123456',
        ]);

        $response->assertStatus(201);
    }
}
