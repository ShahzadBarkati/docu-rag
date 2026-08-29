<?php

namespace App\Services\Documents;

class TextChunker
{
    /** @return array{content: string, char_start: int, char_end: int}[]   */
    public function chunk(string $text, int $chunkSize = 2000, int $overlap = 400): array
    {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) <= $chunkSize) {
            return $text !== ''
                ? [['content' => $text, 'char_start' => 0, 'char_end' => mb_strlen($text)]]
                : [];
        }
        $chunks = [];
        $offset = 0;
        $length = mb_strlen($text);
        while ($offset < $length) {
            $chunk = mb_substr($text, $offset, $chunkSize);
            if ($offset + $chunkSize < $length) {
                $searchFrom = (int) (mb_strlen($chunk) * 0.66);
                $breakAt = $this->lastBreak($chunk, $searchFrom);
                if ($breakAt !== false) {
                    $chunk = mb_substr($chunk, 0, $breakAt + 1);
                    $advance = mb_strlen($chunk) - $overlap;
                } else {
                    $advance = $chunkSize - $overlap;
                }
            } else {
                $advance = mb_strlen($chunk);
            }
            $trimmed = trim($chunk);
            if ($trimmed !== '') {
                $chunks[] = [
                    'content' => $trimmed,
                    'char_start' => $offset,
                    'char_end' => $offset + mb_strlen($chunk),
                ];
            }
            $offset += max($advance, 1);
        }

        return $chunks;
    }

    private function lastBreak(string $text, int $from): int|false
    {
        $substr = mb_substr($text, $from);
        foreach (["\n", '.', '!', '?'] as $char) {
            $pos = strrpos($substr, $char);
            if ($pos !== false) {
                return $from + $pos;
            }
        }
        $pos = strrpos($substr, ' ');
        if ($pos !== false) {
            return $from + $pos;
        }

        return false;
    }
}
