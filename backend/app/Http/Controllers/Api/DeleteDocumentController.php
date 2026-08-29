<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final readonly class DeleteDocumentController
{
    public function __invoke(Document $document): JsonResponse
    {
        Gate::authorize('delete', $document);

        if ($document->path && Storage::disk('local')->exists($document->path)) {
            Storage::disk('local')->delete($document->path);
        }

        $document->chunks()->delete();
        $document->delete();

        return ApiResponse::success(null, 'Document deleted');
    }
}
