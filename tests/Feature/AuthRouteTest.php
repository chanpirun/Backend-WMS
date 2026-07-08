<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthRouteTest extends TestCase
{
    public function test_auth_forgot_password_route_resolves(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should return 200 OK because we prevent email enumeration
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'If that email exists, a password reset link has been sent.'
        ]);
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

    public function test_end_to_end_password_reset_flow(): void
    {
        Mail::fake();

        // 1. Create a temporary user
        $email = 'test_reset_user_' . uniqid() . '@example.com';
        $user = User::create([
            'name' => 'Test Reset User',
            'email' => $email,
            'password' => 'old_password_123', // hashed via User model casts if defined, otherwise plaintext
            'role' => 'member',
        ]);

        try {
            // 2. Request forgot password
            $forgotResponse = $this->postJson('/api/auth/forgot-password', [
                'email' => $email,
            ]);

            $forgotResponse->assertStatus(200);

            // Assert mail was sent to the user
            Mail::assertSent(PasswordResetMail::class, function ($mail) use ($email) {
                return $mail->hasTo($email) && !empty($mail->token) && $mail->email === $email;
            });

            // Retrieve token from database
            $dbRecord = DB::table('password_reset_tokens')->where('email', $email)->first();
            $this->assertNotNull($dbRecord);

            // Get the token sent in the mail.
            $sentToken = null;
            Mail::assertSent(PasswordResetMail::class, function ($mail) use (&$sentToken) {
                $sentToken = $mail->token;
                return true;
            });
            $this->assertNotNull($sentToken);

            // 3. Reset the password using the token
            $resetResponse = $this->postJson('/api/auth/reset-password', [
                'email' => $email,
                'token' => $sentToken,
                'password' => 'new_password_123',
                'password_confirmation' => 'new_password_123',
            ]);

            $resetResponse->assertStatus(200);
            $resetResponse->assertJson(['message' => 'Password has been reset successfully.']);

            // 4. Verify password was updated by attempting login
            $loginResponse = $this->postJson('/api/login', [
                'email' => $email,
                'password' => 'new_password_123',
            ]);
            $loginResponse->assertStatus(200);

        } finally {
            // Clean up
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            $user->delete();
        }
    }
}
