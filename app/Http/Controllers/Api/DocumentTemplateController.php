<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DocumentTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentTemplate::with('creator:id,name');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        return $query->latest()->paginate($request->per_page ?? 50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:document_templates,slug',
            'description' => 'nullable|string',
            'content' => 'required|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $validated['created_by'] = auth()->id();

        $template = DocumentTemplate::create($validated);

        return response()->json($template->load('creator:id,name'), 201);
    }

    public function show(DocumentTemplate $documentTemplate)
    {
        return $documentTemplate->load('creator:id,name');
    }

    public function update(Request $request, DocumentTemplate $documentTemplate)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'nullable|string|max:255|unique:document_templates,slug,' . $documentTemplate->id,
            'description' => 'nullable|string',
            'content' => 'sometimes|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $documentTemplate->update($validated);

        return response()->json($documentTemplate->fresh()->load('creator:id,name'));
    }

    public function destroy(DocumentTemplate $documentTemplate)
    {
        $documentTemplate->delete();
        return response()->json(['message' => 'Template deleted.']);
    }

    public function preview(Request $request, DocumentTemplate $documentTemplate)
    {
        $data = $request->input('data', []);
        $rendered = $documentTemplate->render($data);

        return response()->json(['content' => $rendered]);
    }
}
