<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\SearchRequest;
use App\Services\Documents\EmbeddingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class SearchController
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
    ) {}

    public function __invoke(SearchRequest $request): JsonResponse
    {
        $query = $request->validated('query');
        try {
            $embedding = $this->embeddingService->embed($query);
        } catch (Throwable $e) {
            return ApiResponse::error('Could not embed query: '.$e->getMessage(), 422);
        }
        $embeddingStr = '['.implode(',', $embedding).']';
        $chunks = DB::table('document_chunks')
            ->join('documents', 'document_chunks.document_id', '=', 'documents.id')
            ->where('documents.user_id', $request->user()->id)
            ->selectRaw(
                'document_chunks.content, document_chunks.document_id, documents.name as document_name, 1 - (embedding <=> ?::vector) as similarity',
                [$embeddingStr],
            )
            ->whereRaw('(embedding <=> ?::vector) < 0.4', [$embeddingStr])
            ->orderBy('similarity', 'desc')
            ->limit(5)
            ->get();

        return ApiResponse::success($chunks);
    }
}
