<?php

namespace Tests\Unit;

use App\Services\Documents\TextChunker;
use PHPUnit\Framework\TestCase;

class TextChunkerTest extends TestCase
{
    private TextChunker $chunker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chunker = new TextChunker;
    }

    public function test_it_splits_long_text_into_multiple_chunks(): void
    {
        $text = str_repeat('word ', 500); // 2500 chars, larger than chunk size

        $chunks = $this->chunker->chunk($text);

        $this->assertGreaterThan(1, count($chunks));
    }

    public function test_it_returns_single_chunk_for_short_text(): void
    {
        $chunks = $this->chunker->chunk('Hello world');

        $this->assertCount(1, $chunks);
        $this->assertSame('Hello world', $chunks[0]['content']);
    }

    public function test_chunks_overlap_by_twenty_percent(): void
    {
        $text = implode(' ', array_fill(0, 900, 'word')); // ~4500 chars
        $chunks = $this->chunker->chunk($text, chunkSize: 2000, overlap: 400);

        $this->assertGreaterThan(1, count($chunks));

        $firstCharEnd = $chunks[0]['char_end'];
        $secondCharStart = $chunks[1]['char_start'];

        $overlap = $firstCharEnd - $secondCharStart;
        $this->assertGreaterThan(0, $overlap);
        $this->assertLessThanOrEqual(400, $overlap);
    }

    public function test_it_handles_empty_text(): void
    {
        $this->assertSame([], $this->chunker->chunk(''));
    }

    public function test_it_tracks_character_offsets(): void
    {
        $text = str_repeat('x', 100);
        $chunks = $this->chunker->chunk($text, chunkSize: 100, overlap: 0);

        $this->assertCount(1, $chunks);
        $this->assertSame(0, $chunks[0]['char_start']);
        $this->assertSame(100, $chunks[0]['char_end']);
    }
}
