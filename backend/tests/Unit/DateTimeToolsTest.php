<?php

namespace Tests\Unit;

use App\Ai\Time\CurrentDateTimeProvider;
use App\Ai\Tools\CalculateDateOffset;
use App\Ai\Tools\FormatDateTime;
use App\Ai\Tools\GetCurrentDateTime;
use App\Ai\Tools\GetUserTimezone;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\TestCase;

class DateTimeToolsTest extends TestCase
{
    private CurrentDateTimeProvider $time;

    protected function setUp(): void
    {
        parent::setUp();
        $this->time = new CurrentDateTimeProvider('UTC');
    }

    public function test_current_datetime_tool_returns_iso_datetime(): void
    {
        $tool = new GetCurrentDateTime($this->time);

        $result = $tool->handle(new Request(['timezone' => 'UTC']));

        $this->assertStringContainsString('Current date and time:', $result);
        $this->assertStringContainsString('timezone: UTC', $result);
    }

    public function test_user_timezone_tool_falls_back_to_default(): void
    {
        $tool = new GetUserTimezone($this->time);

        $result = $tool->handle(new Request);

        $this->assertStringContainsString('UTC', $result);
    }

    public function test_user_timezone_tool_uses_provided_timezone(): void
    {
        $tool = new GetUserTimezone($this->time, 'Asia/Karachi');

        $result = $tool->handle(new Request);

        $this->assertStringContainsString('Asia/Karachi', $result);
    }

    public function test_calculate_date_offset_adds_days(): void
    {
        $tool = new CalculateDateOffset($this->time);

        $result = $tool->handle(new Request([
            'offset' => 7,
            'unit' => 'days',
            'date' => '2026-08-29',
        ]));

        $this->assertStringContainsString('2026-09-05', $result);
    }

    public function test_format_datetime_formats_a_date(): void
    {
        $tool = new FormatDateTime($this->time);

        $result = $tool->handle(new Request([
            'date' => '2026-08-29',
            'format' => 'l, F j, Y',
        ]));

        $this->assertSame('Saturday, August 29, 2026', $result);
    }
}
