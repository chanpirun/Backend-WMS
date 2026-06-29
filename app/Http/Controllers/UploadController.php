<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRequest;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Handle the incoming file upload request.
     */
    public function upload(UploadRequest $request)
    {
        $file = $request->file('file');
        
        // Determine target folder based on resource type
        $resource = $request->input('resource', 'uploads');
        
        // Sanitize target folder to allowed values
        $folder = 'uploads';
        if (in_array($resource, ['projects', 'submissions', 'documents', 'covers', 'datasets', 'source', 'finalized-documents', 'team_documents'])) {
            $folder = $resource;
        }

        // Store file inside the public disk under the specified resource folder
        $filename = uniqid() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs($folder, $filename, 'public');

        return response()->json([
            'relative_path' => $path,
            'public_url' => Storage::disk('public')->url($path),
        ], 201);
    }
}
