<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function upload(Model $documentable, UploadedFile $file, array $data = []): Document
    {
        $path = $file->store('documents', 'public');

        return Document::create([
            'documentable_type' => get_class($documentable),
            'documentable_id' => $documentable->id,
            'name' => $data['name'] ?? $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'category' => $data['category'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'notes' => $data['notes'] ?? null,
            'uploaded_by' => $data['uploaded_by'] ?? auth()->id(),
        ]);
    }

    public function delete(Document $document): bool
    {
        Storage::disk('public')->delete($document->path);
        return $document->delete();
    }

    public function getDocumentsFor(Model $documentable)
    {
        return Document::where('documentable_type', get_class($documentable))
            ->where('documentable_id', $documentable->id)
            ->latest()
            ->get();
    }

    public function getExpiringDocuments(int $days = 30)
    {
        return Document::expiringSoon($days)
            ->whereNull('expiry_reminder_sent_at')
            ->with('documentable')
            ->get();
    }

    public function getExpiredDocuments()
    {
        return Document::expired()
            ->with('documentable')
            ->get();
    }
}
