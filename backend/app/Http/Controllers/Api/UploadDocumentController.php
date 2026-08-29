<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreDocumentRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final readonly class UploadDocumentController
{
    public function __invoke(StoreDocumentRequest $request): JsonResponse
    {
        $file = $request->file('file');

        $path = $file->store('documents/'.$request->user()->id, 'local');

        $document = $request->user()->documents()->create([
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'path' => $path,
            'status' => 'uploaded',
        ]);

        return ApiResponse::success($document, 'Document Uploaded', 201);
    }
}
