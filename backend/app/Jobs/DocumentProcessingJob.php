<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\Documents\DocumentTextExtractor;
use App\Services\Documents\DocumentTextQuality;
use App\Services\Documents\EmbeddingService;
use App\Services\Documents\ImageTranscriptionService;
use App\Services\Documents\TextChunker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentProcessingJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 900;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public Document $document) {}

    /**
     * Execute the job.
     */
    public function handle(
        DocumentTextExtractor $extractor,
        TextChunker $chunker,
        EmbeddingService $embeddingService,
        ImageTranscriptionService $transcription,
        DocumentTextQuality $quality,
    ): void {
        $this->document->update(['status' => 'processing']);

        $path = Storage::disk('local')->path($this->document->path);
        $hash = (string) hash_file('sha256', $path);

        if ($this->tryReuseByHash($hash)) {
            return;
        }

        try {
            $content = $this->extractContent($extractor, $transcription, $quality, $path);
        } catch (Throwable $e) {
            Log::error('Document extraction failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);
            $this->fail('Could not extract text from document.');

            return;
        }

        if (trim($content) === '') {
            Log::error('Document extraction returned no content', [
                'document_id' => $this->document->id,
            ]);
            $this->fail('Could not extract text from document.');

            return;
        }

        $chunks = $chunker->chunk($content);

        try {
            $texts = array_map(fn ($c) => $c['content'], $chunks);
            $embeddings = $embeddingService->embedBatch($texts);
        } catch (Throwable $e) {
            Log::error('Embedding failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);
            $this->fail('Could not generate embeddings.');

            return;
        }

        $this->document->chunks()->delete();

        foreach ($chunks as $i => $chunk) {
            $this->document->chunks()->create([
                'chunk_index' => $i,
                'content' => $chunk['content'],
                'char_start' => $chunk['char_start'],
                'char_end' => $chunk['char_end'],
                'parent_chunk_id' => null,
                'token_count' => str_word_count($chunk['content']),
                'embedding' => $embeddings[$i] ?? [],
            ]);
        }

        $this->document->update(['content' => $content, 'content_hash' => $hash, 'status' => 'ready']);
    }

    /**
     * Share chunks between identical files so the same textbook uploaded by
     * thousands of students is extracted and embedded only once.
     */
    private function tryReuseByHash(string $hash): bool
    {
        $source = Document::query()
            ->where('content_hash', $hash)
            ->where('status', 'ready')
            ->where('id', '!=', $this->document->id)
            ->with('chunks')
            ->first();

        if (! $source || $source->chunks->isEmpty()) {
            return false;
        }

        $this->document->chunks()->delete();

        foreach ($source->chunks as $chunk) {
            $this->document->chunks()->create([
                'chunk_index' => $chunk->chunk_index,
                'content' => $chunk->content,
                'char_start' => $chunk->char_start,
                'char_end' => $chunk->char_end,
                'parent_chunk_id' => $chunk->parent_chunk_id,
                'token_count' => $chunk->token_count,
                'embedding' => $chunk->embedding,
            ]);
        }

        $this->document->update([
            'content' => $source->content,
            'content_hash' => $hash,
            'status' => 'ready',
        ]);

        Log::info('Reused chunks from identical document', [
            'document_id' => $this->document->id,
            'source_document_id' => $source->id,
            'chunks' => $source->chunks->count(),
        ]);

        return true;
    }

    private function extractContent(
        DocumentTextExtractor $extractor,
        ImageTranscriptionService $transcription,
        DocumentTextQuality $quality,
        string $path,
    ): string {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return $transcription->transcribeImage($path);
        }

        if ($extension === 'pdf') {
            $textLayer = $this->textLayer($path);

            if ($quality->isUsable($textLayer)) {
                return $textLayer;
            }

            return $transcription->transcribePdf($path);
        }

        return $extractor->extract($path);
    }

    private function textLayer(string $path): string
    {
        exec(sprintf('pdftotext -enc UTF-8 "%s" - 2>/dev/null', $path), $lines, $code);

        if ($code !== 0) {
            return '';
        }

        return trim(implode("\n", $lines));
    }

    /**
     * Mark the document as failed with a friendly status message.
     */
    private function fail(string $message): void
    {
        $this->document->update(['status' => 'failed']);
    }
}
