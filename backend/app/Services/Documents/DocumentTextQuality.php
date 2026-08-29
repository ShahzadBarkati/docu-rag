<?php

namespace App\Services\Documents;

/**
 * Decides whether extracted text is real document content or boilerplate
 * (e.g. a flipbook viewer footer repeated on every page). When the text
 * layer is boilerplate, the pipeline falls back to vision OCR.
 */
final class DocumentTextQuality
{
    private const MIN_MEANINGFUL_CHARS = 120;

    private const MIN_LINE_DIVERSITY = 0.25;

    public function isUsable(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        if (preg_match_all('/[a-zA-Z0-9]/u', $text) < self::MIN_MEANINGFUL_CHARS) {
            return false;
        }

        return $this->lineDiversity($text) >= self::MIN_LINE_DIVERSITY;
    }

    private function lineDiversity(string $text): float
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $lines = array_values(array_filter(array_map(
            fn (string $line): string => preg_replace('/[^a-zA-Z]/u', '', mb_strtolower(trim($line))),
            $lines
        ), fn (string $line): bool => $line !== ''));

        $total = count($lines);
        if ($total === 0) {
            return 0.0;
        }

        return count(array_unique($lines)) / $total;
    }
}
