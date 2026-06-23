<?php

namespace App\Http\Controllers;

use App\Models\ProjectSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectSubmissionController extends Controller
{
    private function normalizeUploadedFileArrays(Request $request, array $fields): void
    {
        foreach ($fields as $field) {
            if ($request->file($field)) {
                continue;
            }

            $files = $request->file($field . '[]');
            if (!$files) {
                continue;
            }

            $request->files->set($field, is_array($files) ? array_values($files) : [$files]);
        }
    }

    private function uploadedFiles(Request $request, string $field): array
    {
        $files = $request->file($field) ?? $request->file($field . '[]');

        if (!$files) {
            return [];
        }

        return is_array($files) ? array_values($files) : [$files];
    }

    public function publicIndex()
    {
        $items = ProjectSubmission::with(['user', 'projectType'])
            ->where('visibility', 'public')
            ->latest()
            ->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $request->query('scope');

        $query = ProjectSubmission::with(['user', 'projectType'])->latest();

        if ($scope === 'group_hub') {
            // Return all submissions where the user is the owner OR is tagged as a team member
            $userId = $user->id;
            $query->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereJsonContains('team_member_ids', $userId);
            });
        } elseif ($user && $user->role === 'member') {
            // Default member view: only their own submissions
            $query->where('user_id', $user->id);
        }
        // Assistants and Directors see all submissions (no extra filter)

        $items = $query->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'member') {
            return response()->json(['message' => 'Only members can submit projects.'], 403);
        }

        $this->normalizeUploadedFileArrays($request, [
            'document',
            'source_code',
            'dataset',
            'project_images',
        ]);

        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'tags'           => 'required|string',
            'project_type_id'=> 'nullable|exists:project_types,id',
            'owner_type'     => 'required|in:individual,team',
            'team_members'   => 'nullable|string',
            'team_member_ids'=> 'nullable|string',
            'description'    => 'required|string',
            'cover_image'    => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
            'document'       => 'required',
            'document.*'     => 'file|mimes:pdf,doc,docx|max:10240',
            'source_code'    => 'required',
            'source_code.*'  => 'file|mimes:zip|max:102400',
            'dataset'        => 'required',
            'dataset.*'      => 'file|mimes:csv,json,xlsx,xls,zip|max:102400',
            'project_images' => 'nullable|array',
            'project_images.*'=> 'file|mimes:pdf,doc,docx,ppt,pptx,txt|max:10240',
            'demo_link'      => 'nullable|url|max:2048',
        ]);

        $tags = array_values(array_filter(array_map('trim', explode(',', $validated['tags']))));
        $teamMembers = [];

        if (!empty($validated['team_members'])) {
            $decoded = json_decode($validated['team_members'], true);
            if (is_array($decoded)) {
                $teamMembers = array_values(array_filter(array_map('trim', $decoded)));
            }
        }

        // Parse team_member_ids — array of integer user IDs
        $teamMemberIds = [];
        if (!empty($validated['team_member_ids'])) {
            $decoded = json_decode($validated['team_member_ids'], true);
            if (is_array($decoded)) {
                $teamMemberIds = array_values(array_filter(array_map('intval', $decoded)));
            }
        }

        $coverFile = $request->file('cover_image');
        $coverImagePath = $coverFile->storeAs(
            'submissions/covers/' . uniqid(),
            $coverFile->getClientOriginalName(),
            'public'
        );

        // document, source_code, and dataset can be single or multiple files.
        $documentFiles = $this->uploadedFiles($request, 'document');
        $documentPaths = [];
        foreach ($documentFiles as $docFile) {
            $documentPaths[] = $docFile->storeAs(
                'submissions/documents/' . uniqid(),
                $docFile->getClientOriginalName(),
                'public'
            );
        }
        $documentPath = $documentPaths[0] ?? null;

        $sourceFiles = $this->uploadedFiles($request, 'source_code');
        $sourcePaths = [];
        foreach ($sourceFiles as $sFile) {
            $sourcePaths[] = $sFile->storeAs(
                'submissions/source/' . uniqid(),
                $sFile->getClientOriginalName(),
                'public'
            );
        }
        $sourceCodePath = $sourcePaths[0] ?? null;

        $datasetFiles = $this->uploadedFiles($request, 'dataset');
        $datasetPaths = [];
        foreach ($datasetFiles as $dFile) {
            $datasetPaths[] = $dFile->storeAs(
                'submissions/datasets/' . uniqid(),
                $dFile->getClientOriginalName(),
                'public'
            );
        }
        $datasetPath = $datasetPaths[0] ?? null;

        $projectImagePaths = [];
        foreach ($this->uploadedFiles($request, 'project_images') as $finalizedDocument) {
            $projectImagePaths[] = $finalizedDocument->storeAs(
                'submissions/finalized-documents/' . uniqid(),
                $finalizedDocument->getClientOriginalName(),
                'public'
            );
        }

        $ownerName = $validated['owner_type'] === 'individual'
            ? $user->name
            : implode(' / ', $teamMembers);

        $submission = ProjectSubmission::create([
            'user_id'            => $user->id,
            'project_type_id'    => $validated['project_type_id'] ?? null,
            'title'              => $validated['title'],
            'tags'               => $tags,
            'owner_type'         => $validated['owner_type'],
            'owner_name'         => $ownerName,
            'team_members'       => $teamMembers,
            'team_member_ids'    => $teamMemberIds,
            'description'        => $validated['description'],
            'cover_image_path'   => $coverImagePath,
            'document_path'      => $documentPath,
            'document_paths'     => $documentPaths,
            'source_code_path'   => $sourceCodePath,
            'source_code_paths'  => $sourcePaths,
            'dataset_path'       => $datasetPath,
            'dataset_paths'      => $datasetPaths,
            'project_image_paths'=> $projectImagePaths,
            'demo_link'          => $validated['demo_link'] ?? null,
            'status'             => 'pending',
            'visibility'         => 'private',
        ]);

        return response()->json(['data' => $submission->load('projectType')], 201);
    }

    public function updateReview(Request $request, ProjectSubmission $submission)
    {
        $user = $request->user();
        if ($user->role !== 'assistant' && $user->role !== 'director') {
            return response()->json(['message' => 'Only assistants and directors can review submissions.'], 403);
        }

        $validated = $request->validate([
            'status'         => 'required|in:approved,rejected,pending',
            'review_comment' => 'nullable|string',
        ]);

        $isPending = $validated['status'] === 'pending';

        $submission->update([
            'status'           => $validated['status'],
            'review_comment'   => $validated['review_comment'] ?? null,
            'reviewed_by_role' => $isPending ? null : $user->role,
            'reviewed_at'      => $isPending ? null : now(),
        ]);

        return response()->json(['data' => $submission->fresh()]);
    }

    public function updateVisibility(Request $request, ProjectSubmission $submission)
    {
        $user = $request->user();
        if ($user->role !== 'assistant' && $user->role !== 'director') {
            return response()->json(['message' => 'Only assistants and directors can change visibility.'], 403);
        }

        $validated = $request->validate([
            'visibility' => 'required|in:public,private',
        ]);

        $submission->update([
            'visibility' => $validated['visibility'],
        ]);

        return response()->json(['data' => $submission->fresh()]);
    }

    public function destroy(Request $request, ProjectSubmission $submission)
    {
        $user = $request->user();

        // Members can delete any submission they are part of:
        // either as the submitter (user_id) or as a tagged team member (team_member_ids)
        if ($user->role === 'member') {
            $teamMemberIds = $submission->team_member_ids ?? [];
            $isMemberOfGroup = $submission->user_id === $user->id
                || in_array($user->id, $teamMemberIds, true);

            if (!$isMemberOfGroup) {
                return response()->json(['message' => 'You are not a member of this project group.'], 403);
            }
        } elseif ($user->role !== 'assistant' && $user->role !== 'director') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $paths = array_filter([
            $submission->cover_image_path,
            $submission->document_path,
            $submission->source_code_path,
            $submission->dataset_path,
            ...($submission->document_paths ?? []),
            ...($submission->source_code_paths ?? []),
            ...($submission->dataset_paths ?? []),
            ...($submission->project_image_paths ?? []),
        ]);

        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }

        $submission->delete();

        return response()->json(['message' => 'Submission deleted successfully.']);
    }
}
