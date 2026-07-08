<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            // Log failed login attempt — never expose which field was wrong
            Log::warning('Failed login attempt', [
                'email' => $credentials['email'],
                'ip'    => $request->ip(),
                'ua'    => $request->userAgent(),
            ]);
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!method_exists($user, 'createToken')) {
            return response()->json([
                'message' => 'Token generation is unavailable.'
            ], 500);
        }

        // Log successful login
        Log::info('User logged in', [
            'user_id' => $user->id,
            'role'    => $user->role,
            'ip'      => $request->ip(),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        // Return only safe user fields — never the full model
        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            // SECURITY: minimum 8 chars, confirmation required
            'password' => 'required|min:8|confirmed',
            // SECURITY: role is NOT accepted from client — always default to member
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'], // hashed via model cast
            'role'     => 'member',               // hardcoded — never trust client
        ]);

        Log::info('New user registered', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return response()->json([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ], 201);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $request->user()->currentAccessToken()->delete();

        Log::info('User logged out', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return response()->json([
            'message' => 'Logged out'
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;
        $user = User::where('email', $email)->first();

        if ($user) {
            // SECURITY: generate a 60-character cryptographically secure token
            $token = Str::random(60);

            // SECURITY: store HASHED token — never plaintext
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                [
                    'token'      => Hash::make($token),
                    'created_at' => now(),
                ]
            );

            try {
                Mail::to($email)->send(new PasswordResetMail($token, $email));
            } catch (\Exception $e) {
                Log::error('Failed to send password reset email', [
                    'email' => $email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Password reset requested', ['email' => $email, 'ip' => $request->ip()]);

        return response()->json([
            'message' => 'If that email exists, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'token'    => 'required_without:code|string',
            'code'     => 'required_without:token|string',
            'password' => 'required|min:8|confirmed',
        ]);

        $token = $request->input('token') ?? $request->input('code');

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Invalid or expired reset token.'
            ], 422);
        }

        // SECURITY: verify token via Hash::check() — hashed comparison
        if (!Hash::check($token, $record->token)) {
            Log::warning('Invalid token attempt during password reset', [
                'email' => $request->email,
                'ip'    => $request->ip(),
            ]);
            return response()->json([
                'message' => 'Invalid or expired reset token.'
            ], 422);
        }

        // Check expiration (15 minutes)
        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json([
                'message' => 'Reset token has expired.'
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // SECURITY: revoke ALL existing tokens so stolen sessions are invalidated
        $user->tokens()->delete();

        // Clear token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        Log::info('Password reset successful', ['user_id' => $user->id, 'ip' => $request->ip()]);

        return response()->json([
            'message' => 'Password has been reset successfully.'
        ]);
    }
}
