<?php

namespace App\Http\Controllers;

use App\Models\ProjectType;
use Illuminate\Http\Request;

class ProjectTypeController extends Controller
{
    /**
     * List all project types (default + custom), ordered by default first then alphabetical.
     */
    public function index()
    {
        $types = ProjectType::orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $types]);
    }

    /**
     * Create a new custom project type (any authenticated user).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:project_types,name',
        ]);

        $type = ProjectType::create([
            'name'       => trim($validated['name']),
            'is_default' => false,
        ]);

        return response()->json(['data' => $type], 201);
    }
}
