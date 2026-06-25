<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function __construct(protected DocumentService $documentService) {}

    public function index(Request $request)
    {
        $query = Document::with('uploader:id,name');

        if ($request->filled('documentable_type') && $request->filled('documentable_id')) {
            $query->where('documentable_type', $request->documentable_type)
                ->where('documentable_id', $request->documentable_id);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('expiring')) {
            $query->expiringSoon((int) $request->expiring);
        }

        if ($request->boolean('expired')) {
            $query->expired();
        }

        return $query->latest()->paginate($request->per_page ?? 50);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
            'file' => 'required|file|max:10240',
            'name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'expires_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $documentableType = $validated['documentable_type'];
        $documentable = $documentableType::findOrFail($validated['documentable_id']);

        $document = $this->documentService->upload($documentable, $request->file('file'), $validated);

        return response()->json($document->load('uploader:id,name'), 201);
    }

    public function show(Document $document)
    {
        return $document->load('uploader:id,name');
    }

    public function update(Request $request, Document $document)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category' => 'nullable|string|max:100',
            'expires_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $document->update($validated);

        return response()->json($document->fresh()->load('uploader:id,name'));
    }

    public function destroy(Document $document)
    {
        $this->documentService->delete($document);
        return response()->json(['message' => 'Document deleted.']);
    }

    public function download(Document $document)
    {
        if (!Storage::disk('public')->exists($document->path)) {
            return response()->json(['message' => 'File not found.'], 404);
        }

        return Storage::disk('public')->download($document->path, $document->name);
    }

    public function categories()
    {
        $categories = Document::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return response()->json($categories);
    }

    public function stats()
    {
        $total = Document::count();
        $expired = Document::expired()->count();
        $expiringSoon = Document::expiringSoon(30)->count();
        $byCategory = Document::selectRaw('category, count(*) as total')
            ->whereNotNull('category')
            ->groupBy('category')
            ->pluck('total', 'category');

        return response()->json([
            'total' => $total,
            'expired' => $expired,
            'expiring_soon' => $expiringSoon,
            'by_category' => $byCategory,
        ]);
    }
}
