<?php

namespace App\Services\Documents;

use Laravel\Ai\Embeddings;
use Laravel\Ai\Enums\Lab;
use RuntimeException;

class EmbeddingService
{
    private string $model;

    public function __construct()
    {
        $this->model = config('ai.providers.gemini.models.embeddings.default', 'gemini-embedding-001');
    }

    public function embed(string $text, string $taskType = 'RETRIEVAL_QUERY'): array
    {
        $response = Embeddings::for([$text])
            ->generate(provider: Lab::Gemini, model: $this->model);

        $embeddings = $response->embeddings;

        if (empty($embeddings[0])) {
            throw new RuntimeException('Embedding API returned empty result');
        }

        return $embeddings[0];
    }

    public function embedBatch(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT'): array
    {
        if (empty($texts)) {
            return [];
        }

        $response = Embeddings::for($texts)
            ->generate(provider: Lab::Gemini, model: $this->model);

        return $response->embeddings;
    }
}
