<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\GroupContribution;
use App\Models\ProjectSubmission;
use Illuminate\Support\Facades\Storage;

class GroupContributionController extends Controller
{
    private function checkAccess(Request $request, ProjectSubmission $submission): bool
    {
        $user = $request->user();
        if ($user->role === 'assistant' || $user->role === 'director') {
            return true;
        }

        $teamMemberIds = $submission->team_member_ids ?? [];
        return $submission->user_id === $user->id || in_array($user->id, $teamMemberIds, true);
    }

    public function indexAll(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'assistant' || $user->role === 'director') {
            $contributions = GroupContribution::with(['user', 'projectSubmission'])
                ->latest()
                ->get();
        } else {
            $userId = $user->id;
            $projectIds = ProjectSubmission::where('user_id', $userId)
                ->orWhereJsonContains('team_member_ids', $userId)
                ->pluck('id');

            $contributions = GroupContribution::whereIn('project_submission_id', $projectIds)
                ->with(['user', 'projectSubmission'])
                ->latest()
                ->get();
        }

        return response()->json([
            'data' => $contributions,
        ]);
    }

    public function index(Request $request, ProjectSubmission $submission)
    {
        if (!$this->checkAccess($request, $submission)) {
            return response()->json(['message' => 'You do not have access to this project.'], 403);
        }

        $contributions = $submission->contributions()->with('user')->latest()->get();

        return response()->json([
            'data' => $contributions,
        ]);
    }

    public function store(Request $request, ProjectSubmission $submission)
    {
        if (!$this->checkAccess($request, $submission)) {
            return response()->json(['message' => 'You do not have access to this project.'], 403);
        }

        $validated = $request->validate([
            'category' => 'required|string|in:manuscript,frontend,backend,database,postman',
            // SECURITY: explicit MIME whitelist — no PHP, no executables, no HTML
            'file'     => [
                'required',
                'file',
                'max:102400',
                'mimes:pdf,doc,docx,ppt,pptx,txt,zip,csv,xlsx,xls,png,jpg,jpeg,webp,sql,json',
            ],
        ]);


        $file = $request->file('file');
        $filePath = $file->storeAs(
            'contributions/' . uniqid(),
            $file->getClientOriginalName(),
            'public'
        );

        $contribution = GroupContribution::create([
            'project_submission_id' => $submission->id,
            'user_id'               => $request->user()->id,
            'category'              => $validated['category'],
            'file_path'             => $filePath,
            'file_name'             => $file->getClientOriginalName(),
        ]);

        return response()->json([
            'data' => $contribution->load('user'),
        ], 201);
    }

    public function destroy(Request $request, GroupContribution $contribution)
    {
        $user = $request->user();
        $submission = $contribution->projectSubmission;

        $isUploader = $contribution->user_id === $user->id;
        $isProjectOwner = $submission && $submission->user_id === $user->id;
        $isAdminOrStaff = $user->role === 'assistant' || $user->role === 'director';

        if (!$isUploader && !$isProjectOwner && !$isAdminOrStaff) {
            return response()->json(['message' => 'You are not authorized to delete this contribution.'], 403);
        }

        // Delete from storage
        if ($contribution->file_path) {
            Storage::disk('public')->delete($contribution->file_path);
        }

        $contribution->delete();

        return response()->json([
            'message' => 'Contribution deleted successfully.',
        ]);
    }
}
