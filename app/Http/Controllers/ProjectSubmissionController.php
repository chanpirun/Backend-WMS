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

    public function index(Request $request)
    {
        $user = $request->user();

        $query = ProjectSubmission::with('user')->latest();

        // Members should only see their own submissions.
        if ($user && $user->role === 'member') {
            $query->where('user_id', $user->id);
        }

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
            'title' => 'required|string|max:255',
            'tags' => 'required|string',
            'owner_type' => 'required|in:individual,team',
            'team_members' => 'nullable|string',
            'description' => 'required|string',
            'cover_image' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
            'document' => 'required',
            'document.*' => 'file|mimes:pdf,doc,docx|max:10240',
            'source_code' => 'required',
            'source_code.*' => 'file|mimes:zip|max:20480',
            'dataset' => 'required',
            'dataset.*' => 'file|mimes:csv,json,xlsx,xls,zip|max:20480',
            'project_images' => 'nullable|array',
            'project_images.*' => 'file|mimes:pdf,doc,docx,ppt,pptx,txt|max:10240',
            'demo_link' => 'nullable|url|max:2048',
        ]);

        $tags = array_values(array_filter(array_map('trim', explode(',', $validated['tags']))));
        $teamMembers = [];

        if (!empty($validated['team_members'])) {
            $decoded = json_decode($validated['team_members'], true);
            if (is_array($decoded)) {
                $teamMembers = array_values(array_filter(array_map('trim', $decoded)));
            }
        }

        $coverImagePath = $request->file('cover_image')->store('submissions/covers', 'public');

        // document, source_code, and dataset can be single or multiple files.
        $documentFiles = $this->uploadedFiles($request, 'document');
        $documentPaths = [];
        foreach ($documentFiles as $docFile) {
            $documentPaths[] = $docFile->store('submissions/documents', 'public');
        }
        $documentPath = $documentPaths[0] ?? null;

        $sourceFiles = $this->uploadedFiles($request, 'source_code');
        $sourcePaths = [];
        foreach ($sourceFiles as $sFile) {
            $sourcePaths[] = $sFile->store('submissions/source', 'public');
        }
        $sourceCodePath = $sourcePaths[0] ?? null;

        $datasetFiles = $this->uploadedFiles($request, 'dataset');
        $datasetPaths = [];
        foreach ($datasetFiles as $dFile) {
            $datasetPaths[] = $dFile->store('submissions/datasets', 'public');
        }
        $datasetPath = $datasetPaths[0] ?? null;

        $projectImagePaths = [];
        foreach ($this->uploadedFiles($request, 'project_images') as $finalizedDocument) {
            $projectImagePaths[] = $finalizedDocument->store('submissions/finalized-documents', 'public');
        }

        $ownerName = $validated['owner_type'] === 'individual'
            ? $user->name
            : implode(' / ', $teamMembers);

        $submission = ProjectSubmission::create([
            'user_id' => $user->id,
            'title' => $validated['title'],
            'tags' => $tags,
            'owner_type' => $validated['owner_type'],
            'owner_name' => $ownerName,
            'team_members' => $teamMembers,
            'description' => $validated['description'],
            'cover_image_path' => $coverImagePath,
            'document_path' => $documentPath,
            'document_paths' => $documentPaths,
            'source_code_path' => $sourceCodePath,
            'source_code_paths' => $sourcePaths,
            'dataset_path' => $datasetPath,
            'dataset_paths' => $datasetPaths,
            'project_image_paths' => $projectImagePaths,
            'demo_link' => $validated['demo_link'] ?? null,
            'status' => 'pending',
            'visibility' => 'private',
        ]);

        return response()->json(['data' => $submission], 201);
    }

    public function updateReview(Request $request, ProjectSubmission $submission)
    {
        $user = $request->user();
        if ($user->role !== 'assistant' && $user->role !== 'director') {
            return response()->json(['message' => 'Only assistants and directors can review submissions.'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,pending',
            'review_comment' => 'nullable|string',
        ]);

        $isPending = $validated['status'] === 'pending';

        $submission->update([
            'status' => $validated['status'],
            'review_comment' => $validated['review_comment'] ?? null,
            'reviewed_by_role' => $isPending ? null : $user->role,
            'reviewed_at' => $isPending ? null : now(),
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
        if ($user->role !== 'assistant' && $user->role !== 'director') {
            return response()->json(['message' => 'Only assistants and directors can delete submissions.'], 403);
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
