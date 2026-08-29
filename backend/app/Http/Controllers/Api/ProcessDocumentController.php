<?php

namespace App\Http\Controllers\Api;

use App\Jobs\DocumentProcessingJob;
use App\Models\Document;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final readonly class ProcessDocumentController
{
    public function __invoke(Request $request, Document $document)
    {
        Gate::authorize('process', $document);

        $document->update(['status' => 'processing']);

        DocumentProcessingJob::dispatch($document);

        return ApiResponse::success([
            'id' => $document->id,
            'status' => 'processing',
        ], 'Document queued for processing');
    }
}
