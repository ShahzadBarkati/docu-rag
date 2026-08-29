<?php

namespace App\Services\Documents;

use RuntimeException;
use Smalot\PdfParser\Parser;
use ZipArchive;

class DocumentTextExtractor
{
    public function extract(string $absolutePath): string
    {
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'txt' => $this->fromTxt($absolutePath),
            'csv' => $this->fromCsv($absolutePath),
            'pdf' => $this->fromPdf($absolutePath),
            'html' => $this->fromHtml($absolutePath),
            'docx' => $this->fromDocx($absolutePath),
            default => throw new RuntimeException("Unsupported document type: { $extension }"),
        };
    }

    private function fromTxt(string $path): string
    {
        return trim((string) file_get_contents($path));
    }

    private function fromCsv(string $path): string
    {
        $lines = [];
        $handle = fopen($path, 'r');

        while (($row = fgetcsv($handle)) !== false) {
            $lines[] = implode(' ', array_map(
                fn ($value) => is_scalar($value) ? $value : '',
                $row
            ));
        }

        fclose($handle);

        return trim(implode("\n", $lines));
    }

    private function fromHtml(string $path)
    {
        $raw = (string) file_get_contents($path);
        $withoutScripts = preg_replace('#<(script|style)[^>]*>.*?</\1>#is', ' ', $raw);
        $text = strip_tags((string) $withoutScripts);
        $text = html_entity_decode($text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function fromPdf(string $path): string
    {
        $text = (new Parser)->parseFile($path)->getText();

        return trim((string) preg_replace('/[ \t]+/', ' ', $text ?? ''));
    }

    private function fromDocx(string $path): string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException("Cannot open docx archive: {$path}");
        }
        try {
            $xml = (string) $zip->getFromName('word/document.xml');
        } finally {
            $zip->close();
        }
        $xml = str_replace('</w:p>', "\n", $xml);
        preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $xml, $matches);

        return trim(html_entity_decode(implode('', $matches[1])));
    }
}
