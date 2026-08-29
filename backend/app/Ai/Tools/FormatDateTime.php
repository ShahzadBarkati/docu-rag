<?php

namespace App\Ai\Tools;

use App\Ai\Time\CurrentDateTimeProvider;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class FormatDateTime implements Tool
{
    public function __construct(
        private readonly CurrentDateTimeProvider $time,
    ) {}

    public function description(): Stringable|string
    {
        return 'Format a date/time value into a human-friendly string using a PHP date format. Use this when the user wants a date written out in a particular way, such as "January 1, 2026" or "Monday".';
    }

    public function handle(Request $request): Stringable|string
    {
        $dateValue = $request['date'] ?? null;

        $value = $dateValue !== null && $dateValue !== ''
            ? $this->time->parse($dateValue, $request['timezone'] ?? null)
            : $this->time->now($request['timezone'] ?? null);

        return $value->format($request['format']);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()
                ->description('Optional date/datetime to format. Defaults to the current date/time when omitted.'),
            'format' => $schema->string()
                ->description('PHP date format string, e.g. "Y-m-d", "l, F j, Y", or "H:i:s".')
                ->required(),
            'timezone' => $schema->string()
                ->description('Optional IANA timezone for interpreting the date.'),
        ];
    }
}
