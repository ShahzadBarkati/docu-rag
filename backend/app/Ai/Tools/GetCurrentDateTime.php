<?php

namespace App\Ai\Tools;

use App\Ai\Time\CurrentDateTimeProvider;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCurrentDateTime implements Tool
{
    public function __construct(
        private readonly CurrentDateTimeProvider $time,
    ) {}

    public function description(): Stringable|string
    {
        return 'Get the current date and time. Use this when the user asks what the current date, time, day, "today", "now", or a deadline relative to today is. Optionally provide an IANA timezone (e.g. "America/New_York") to get the time in that zone.';
    }

    public function handle(Request $request): Stringable|string
    {
        $timezone = $request['timezone'] ?? null;

        $now = $this->time->now($timezone);

        return sprintf(
            'Current date and time: %s (timezone: %s)',
            $now->toDateTimeString(),
            $this->time->resolveTimezone($timezone),
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'timezone' => $schema->string()
                ->description('Optional IANA timezone identifier, e.g. "UTC" or "Asia/Karachi".'),
        ];
    }
}
