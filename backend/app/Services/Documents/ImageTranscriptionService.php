<?php

namespace App\Services\Documents;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Transcribes image-based content (phone photos, scanned/image-only PDFs)
 * using the configured Gemini vision model. Renders PDF pages with poppler
 * and sends them in small batches to keep request payloads small.
 */
final class ImageTranscriptionService
{
    private const PROMPT = <<<'PROMPT'
Transcribe the text on this page verbatim, preserving structure and layout.
Keep numbers, Roman numerals, mathematical notation, and tables exactly as shown.
Output only the transcribed text. If a page contains no text, output "(notext)".
PROMPT;

    private const BATCH_PAGES = 4;

    private const MAX_INLINE_BYTES = 8_000_000;

    private string $baseUrl;

    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            (string) config('ai.providers.gemini.url', 'https://generativelanguage.googleapis.com/v1beta/'),
            '/'
        ).'/';
        $this->apiKey = (string) config('ai.providers.gemini.key');
        $this->model = (string) config('ai.providers.gemini.models.text.default', 'gemini-3.6-flash');
    }

    public function transcribeImage(string $absolutePath): string
    {
        return $this->transcribePaths([$absolutePath]);
    }

    public function transcribePdf(string $absolutePath): string
    {
        $tempDir = $this->renderToImages($absolutePath);

        try {
            $paths = glob(rtrim($tempDir, '/').'/*.png') ?: [];
            natsort($paths);
            $paths = array_values($paths);

            if ($paths === []) {
                throw new RuntimeException('PDF rendered no pages to OCR.');
            }

            return $this->transcribePaths($paths);
        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    /**
     * @param  string[]  $paths
     */
    private function transcribePaths(array $paths): string
    {
        $output = [];

        foreach (array_chunk($paths, self::BATCH_PAGES) as $batch) {
            $output[] = $this->ask($this->buildParts($batch));
        }

        return trim(implode("\n\n", array_filter($output)));
    }

    /**
     * @param  string[]  $paths
     * @return array<int, array<string, mixed>>
     */
    private function buildParts(array $paths): array
    {
        $parts = [['text' => self::PROMPT]];

        foreach ($paths as $i => $path) {
            $bytes = (int) filesize($path);

            if ($bytes === 0 || $bytes > self::MAX_INLINE_BYTES) {
                $parts[] = ['text' => '--- PAGE '.($i + 1).': (notext) ---'];

                continue;
            }

            $parts[] = ['text' => '--- PAGE '.($i + 1).' ---'];
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $this->mimeType($path),
                    'data' => base64_encode((string) file_get_contents($path)),
                ],
            ];
        }

        return $parts;
    }

    /**
     * @param  array<int, array<string, mixed>>  $parts
     */
    private function ask(array $parts): string
    {
        $payload = [
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'temperature' => 0.0,
            ],
        ];

        $response = Http::timeout(180)
            ->retry(3, 1500, function (Throwable $exception): bool {
                if ($exception instanceof ConnectionException) {
                    return true;
                }

                if ($exception instanceof RequestException && $exception->response) {
                    return $exception->response->status() === 429 || $exception->response->serverError();
                }

                return false;
            })
            ->baseUrl($this->baseUrl)
            ->asJson()
            ->post('models/'.$this->model.':generateContent?key='.rawurlencode($this->apiKey), $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'Gemini vision request failed (HTTP '.$response->status().'): '.mb_substr((string) $response->body(), 0, 300)
            );
        }

        $candidates = $response->json('candidates');

        if (! is_array($candidates) || ! isset($candidates[0]['content']['parts']) || ! is_array($candidates[0]['content']['parts'])) {
            Log::warning('Gemini vision returned no candidates for a page batch', [
                'status' => $response->status(),
                'reason' => (string) $response->json('promptFeedback.blockReason'),
            ]);

            return '';
        }

        return trim(implode('', array_column($candidates[0]['content']['parts'], 'text')));
    }

    private function renderToImages(string $pdfPath): string
    {
        $tempDir = sys_get_temp_dir().'/ocr_'.bin2hex(random_bytes(6));
        mkdir($tempDir, 0777, true);

        $command = sprintf('pdftoppm -r 150 -png "%s" "%s/page" 2>&1', $pdfPath, $tempDir);
        exec($command, $output, $code);

        if ($code !== 0) {
            $this->deleteDirectory($tempDir);
            throw new RuntimeException('Failed to render PDF pages: '.implode('; ', array_slice($output ?? [], -5)));
        }

        return $tempDir;
    }

    private function mimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpeg', 'jpg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = glob(rtrim($dir, '/').'/*') ?: [];

        foreach ($files as $file) {
            is_dir($file) ? $this->deleteDirectory($file) : @unlink($file);
        }

        @rmdir($dir);
    }
}
