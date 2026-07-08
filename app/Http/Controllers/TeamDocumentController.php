<?php

namespace App\Http\Controllers;

use App\Models\TeamDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamDocumentController extends Controller
{
    /**
     * List all team documents visible to the authenticated user.
     * A document is visible if:
     *   - The user submitted it, OR
     *   - The user is tagged as a co-author, OR
     *   - The user is an assistant/director (admin access)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'assistant' || $user->role === 'director') {
            $docs = TeamDocument::with('user')->latest()->get();
        } else {
            $userId = $user->id;
            $docs = TeamDocument::with('user')
                ->where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)
                      ->orWhereJsonContains('tagged_member_ids', $userId);
                })
                ->latest()
                ->get();
        }

        return response()->json(['data' => $docs]);
    }

    /**
     * Create a new standalone team document contribution.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'               => 'required|string|max:255',
            'description'         => 'nullable|string|max:2000',
            'tagged_member_ids'   => 'nullable|string',   // JSON-encoded array
            'tagged_member_names' => 'nullable|string',   // JSON-encoded array
            'manual_doc'          => 'nullable|file|mimes:pdf,doc,docx|max:30720',
            'source_code'         => 'nullable|file|mimes:zip|max:102400',
            'database_file'       => 'nullable|file|mimes:csv,json,xlsx,xls,zip,sql,db,txt|max:51200',
            'final_doc'           => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,txt|max:51200',
        ]);

        $user   = $request->user();
        $folder = 'team_documents/' . uniqid();

        // Decode and validate tagged member arrays
        $taggedIds = [];
        if ($request->input('tagged_member_ids')) {
            $decoded = json_decode($request->input('tagged_member_ids'), true);
            if (is_array($decoded)) {
                $taggedIds = array_values(array_filter(array_map('intval', $decoded)));
            }
        }

        if (!empty($taggedIds)) {
            $validUserCount = \App\Models\User::whereIn('id', $taggedIds)->count();
            if ($validUserCount !== count($taggedIds)) {
                return response()->json(['message' => 'One or more tagged member IDs are invalid.'], 422);
            }
            $hasDirectors = \App\Models\User::whereIn('id', $taggedIds)->where('role', 'director')->exists();
            if ($hasDirectors) {
                return response()->json(['message' => 'Cannot tag directors in team documents.'], 422);
            }
        }

        // Helper to store a file and return [path, name]
        $storeFile = function (string $inputName) use ($request, $folder): array {
            if (!$request->hasFile($inputName)) {
                return [null, null];
            }
            $file = $request->file($inputName);
            $path = $file->storeAs($folder, $file->getClientOriginalName(), 'public');
            return [$path, $file->getClientOriginalName()];
        };

        [$manualPath, $manualName]   = $storeFile('manual_doc');
        [$sourcePath, $sourceName]   = $storeFile('source_code');
        [$dbPath,     $dbName]       = $storeFile('database_file');
        [$finalPath,  $finalName]    = $storeFile('final_doc');

        $taggedNames = $request->input('tagged_member_names')
            ? json_decode($request->input('tagged_member_names'), true)
            : [];

        $doc = TeamDocument::create([
            'user_id'             => $user->id,
            'title'               => $request->input('title'),
            'description'         => $request->input('description'),
            'tagged_member_ids'   => $taggedIds,
            'tagged_member_names' => $taggedNames,
            'manual_doc_path'     => $manualPath,
            'manual_doc_name'     => $manualName,
            'source_code_path'    => $sourcePath,
            'source_code_name'    => $sourceName,
            'database_path'       => $dbPath,
            'database_name'       => $dbName,
            'final_doc_path'      => $finalPath,
            'final_doc_name'      => $finalName,
        ]);

        return response()->json(['data' => $doc->load('user')], 201);
    }

    /**
     * Delete a team document. Only the submitter or admin can delete.
     */
    public function destroy(Request $request, TeamDocument $teamDocument)
    {
        $user = $request->user();

        if (
            $teamDocument->user_id !== $user->id &&
            $user->role !== 'assistant' &&
            $user->role !== 'director'
        ) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Remove stored files
        foreach (['manual_doc_path', 'source_code_path', 'database_path', 'final_doc_path'] as $col) {
            if ($teamDocument->$col) {
                Storage::disk('public')->delete($teamDocument->$col);
            }
        }

        $teamDocument->delete();

        return response()->json(['message' => 'Deleted successfully.']);
    }
}
