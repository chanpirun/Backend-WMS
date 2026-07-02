<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthRouteTest extends TestCase
{
    public function test_auth_forgot_password_route_resolves(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should return 422 validation failure because email doesn't exist, not 404
        $response->assertStatus(422);
    }

    public function test_auth_reset_password_route_resolves(): void
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'nonexistent@example.com',
            'code' => '123456',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // Should return 422 validation failure because email doesn't exist, not 404
        $response->assertStatus(422);
    }
}
