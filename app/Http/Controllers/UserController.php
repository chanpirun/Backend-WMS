<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ProjectSubmission;
use App\Mail\MemberInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'member',
            ]);

            return response()->json([
                'message' => 'Member recruited successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating member',
                'error' => $e->getMessage()
            ], 500);
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

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($id),
            ],
            'password' => 'sometimes|min:8|string',
            'role' => 'sometimes|string|in:member,assistant,director',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Delete/remove a member
     */
    public function destroy(Request $request, $id)
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

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Invite a friend by email to Group Hub and automatically add them to the inviter's project submissions.
     */
    public function invite(Request $request)
    {
        $inviter = $request->user();

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
            $ids = $submission->team_member_ids ?? [];
            $names = $submission->team_members ?? [];

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
