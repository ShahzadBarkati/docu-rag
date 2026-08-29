<?php

namespace App\Console\Commands;

use App\Services\Documents\EmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReEmbedChunksCommand extends Command
{
    protected $signature = 'chunks:re-embed';

    protected $description = 'Re-embed all document chunks with new model (3072 dimensions)';

    public function handle(EmbeddingService $embeddingService): int
    {
        $totalChunks = DB::table('document_chunks')->count();
        $this->info("Found {$totalChunks} chunks to re-embed.");

        if ($totalChunks === 0) {
            $this->info('Nothing to do.');

            return self::SUCCESS;
        }

        if (! $this->confirm('This will call the Gemini API for every chunk. Continue?')) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalChunks);
        $bar->start();

        $batchSize = 20;
        $offset = 0;
        $errors = 0;

        while ($offset < $totalChunks) {
            $chunks = DB::table('document_chunks')
                ->orderBy('id')
                ->offset($offset)
                ->limit($batchSize)
                ->get();

            if ($chunks->isEmpty()) {
                break;
            }

            $texts = $chunks->pluck('content')->toArray();
            $ids = $chunks->pluck('id')->toArray();

            try {
                $embeddings = $embeddingService->embedBatch($texts);

                foreach ($ids as $i => $id) {
                    DB::table('document_chunks')
                        ->where('id', $id)
                        ->update(['embedding' => $embeddings[$i] ?? []]);
                }
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("Batch starting at offset {$offset} failed: ".$e->getMessage());
                $errors++;
            }

            $bar->advance($batchSize);
            $offset += $batchSize;
        }

        $bar->finish();
        $this->newLine();

        if ($errors > 0) {
            $this->error("Completed with {$errors} errors.");

            return self::FAILURE;
        }

        $this->info('All chunks re-embedded successfully.');

        return self::SUCCESS;
    }
}
