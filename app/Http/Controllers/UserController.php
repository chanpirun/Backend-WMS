<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ProjectSubmission;
use App\Mail\MemberInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Get all members recruited by the director or all members
     */
    public function index(Request $request)
    {
        // Only directors can view members
        if ($request->user()->role !== 'director') {
            return response()->json([
                'message' => 'Unauthorized. Only directors can view members.'
            ], 403);
        }

        $members = User::select('id', 'name', 'email', 'role', 'created_at')
            ->get();

        return response()->json([
            'members' => $members,
            'count' => $members->count(),
        ]);
    }

    /**
     * Recruit/Create a new member
     */
    public function store(Request $request)
    {
        // Only directors can recruit members
        if ($request->user()->role !== 'director') {
            return response()->json([
                'message' => 'Unauthorized. Only directors can recruit members.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email'),
            ],
            'password' => 'required|min:8|string',
        ]);

        try {
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role'     => 'member',
            ]);

            Log::info('Member created by director', [
                'created_by' => $request->user()->id,
                'new_user'   => $user->id,
            ]);

            return response()->json([
                'message' => 'Member recruited successfully',
                'user'    => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'role'       => $user->role,
                    'created_at' => $user->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            // SECURITY: never expose raw exception messages to the client
            Log::error('Member creation failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An internal error occurred.'], 500);
        }
    }

    /**
     * Get a specific member
     */
    public function show(Request $request, $id)
    {
        if ($request->user()->role !== 'director') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json(['user' => $user]);
    }

    /**
     * Update member information
     */
    public function update(Request $request, $id)
    {
        $currentUser = $request->user();

        if ($currentUser->role !== 'director') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // SECURITY: Directors cannot change their own role
        if ((int) $id === $currentUser->id) {
            return response()->json(['message' => 'You cannot modify your own account through this endpoint.'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($id)],
            'password' => 'sometimes|min:8|string',
            // SECURITY: 'director' is excluded — directors cannot promote others to director via API
            'role'     => 'sometimes|string|in:member,assistant',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        Log::info('User role/info updated', [
            'updated_by' => $currentUser->id,
            'target_user' => $user->id,
            'changes' => array_keys($validated),
        ]);

        return response()->json([
            'message' => 'User updated successfully',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role,
            ]
        ]);
    }

    /**
     * Delete/remove a member
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();

        if ($currentUser->role !== 'director') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // SECURITY: Cannot delete your own account
        if ((int) $id === $currentUser->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 403);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // SECURITY: Cannot delete other director accounts
        if ($user->role === 'director') {
            return response()->json(['message' => 'Director accounts cannot be deleted through this endpoint.'], 403);
        }

        Log::info('User deleted', [
            'deleted_by'  => $currentUser->id,
            'target_user' => $user->id,
            'target_role' => $user->role,
        ]);

        $user->tokens()->delete(); // revoke all sessions before deleting
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Invite a friend by email to Group Hub and automatically add them to the inviter's project submissions.
     */
    public function invite(Request $request)
    {
        $inviter = $request->user();

        // SECURITY VULN-013: Add explicit role check
        if ($inviter->role !== 'member' && $inviter->role !== 'director') {
            return response()->json(['message' => 'Only members and directors can invite others.'], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($validated['email']));

        // Check if user already exists
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User with this email not found. They must register first.'
            ], 404);
        }

        // Find all project submissions where the inviter is the owner
        $submissions = ProjectSubmission::where('user_id', $inviter->id)->get();

        foreach ($submissions as $submission) {
            $ids = is_array($submission->team_member_ids) ? $submission->team_member_ids : [];
            $names = is_array($submission->team_members) ? $submission->team_members : [];

            if (!in_array($user->id, $ids)) {
                $ids[] = $user->id;
                $names[] = $user->name;

                $submission->update([
                    'team_member_ids' => $ids,
                    'team_members' => $names,
                    'owner_type' => 'team',
                    'owner_name' => $submission->owner_name . ' / ' . $user->name
                ]);
            }
        }

        try {
            Mail::to($email)->send(new MemberInvitationMail($inviter, $user, null));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Member added to projects, but invitation email failed to send.',
                'error' => $e->getMessage(),
                'user' => $user
            ], 200);
        }

        return response()->json([
            'message' => 'Member added to your projects and invitation email sent successfully.',
            'user' => $user
        ], 200);
    }
}
