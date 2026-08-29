<?php

namespace Tests\Unit;

use App\Services\Documents\DocumentTextQuality;
use PHPUnit\Framework\TestCase;

class DocumentTextQualityTest extends TestCase
{
    private DocumentTextQuality $quality;

    protected function setUp(): void
    {
        parent::setUp();
        $this->quality = new DocumentTextQuality;
    }

    public function test_real_prose_is_usable(): void
    {
        $text = implode("\n", [
            'The Roman numeral system uses combinations of letters.',
            'I equals one, V equals five, and X equals ten.',
            'Smaller numbers placed after larger numbers are added.',
            'For example, the number seven is written as VII.',
            'This rule makes it easy to read numbers at a glance.',
        ]);

        $this->assertTrue($this->quality->isUsable($text));
    }

    public function test_empty_text_is_not_usable(): void
    {
        $this->assertFalse($this->quality->isUsable(''));
        $this->assertFalse($this->quality->isUsable('   '));
    }

    public function test_tiny_fragments_are_not_usable(): void
    {
        $this->assertFalse($this->quality->isUsable('VII'));
    }

    public function test_repeated_viewer_boilerplate_is_not_usable(): void
    {
        $footer = '8/27/26, 8:47 PM Maharashtra-board-class-5-Maths-Textbook https://online.fliphtml5.com/uwuph/btpc/#p=10';
        $text = implode("\n", array_fill(0, 8, $footer));

        $this->assertFalse($this->quality->isUsable($text));
    }

    public function test_boilerplate_with_some_real_text_is_usable(): void
    {
        $text = implode("\n", [
            'Maharashtra State Bureau of Textbook Production',
            'Mathematics Textbook For Standard Five',
            'Roman numerals are used to write numbers in an ancient system.',
            'I represents one and V represents five.',
            'Chapter 3: Roman Numerals, Page 12.',
        ]);

        $this->assertTrue($this->quality->isUsable($text));
    }
}
