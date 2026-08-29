<?php

namespace App\Ai\Tools;

use App\Ai\Time\CurrentDateTimeProvider;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CalculateDateOffset implements Tool
{
    public function __construct(
        private readonly CurrentDateTimeProvider $time,
    ) {}

    public function description(): Stringable|string
    {
        return 'Add or subtract an amount of time to/from a date and return the resulting date/time. Use this for questions like "what is the date 7 days from now?", "what was the date 1 month ago?", or "what day will it be in 2 weeks?".';
    }

    public function handle(Request $request): Stringable|string
    {
        $dateValue = $request['date'] ?? null;

        $date = $dateValue !== null && $dateValue !== ''
            ? $this->time->parse($dateValue, $request['timezone'] ?? null)
            : $this->time->now($request['timezone'] ?? null);

        $offset = $request['offset'];
        $unit = $request['unit'] ?? 'days';
        $timezone = $request['timezone'] ?? null;

        $result = $this->applyOffset($date, $offset, $unit);

        return sprintf(
            '%s added to %s equals: %s (timezone: %s)',
            $this->describeOffset($offset, $unit),
            $date->toDateTimeString(),
            $result->toDateTimeString(),
            $this->time->resolveTimezone($timezone),
        );
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'offset' => $schema->integer()
                ->description('The amount of time to add (positive) or subtract (negative).')
                ->required(),
            'unit' => $schema->string()
                ->description('The unit of the offset: days, weeks, months, or years.')
                ->enum(['days', 'weeks', 'months', 'years'])
                ->default('days'),
            'date' => $schema->string()
                ->description('Optional base date/datetime. Defaults to the current date/time when omitted.'),
            'timezone' => $schema->string()
                ->description('Optional IANA timezone for interpreting the result.'),
        ];
    }

    private function applyOffset(CarbonImmutable $date, int $offset, string $unit): CarbonImmutable
    {
        return match ($unit) {
            'weeks' => $date->addWeeks($offset),
            'months' => $date->addMonths($offset),
            'years' => $date->addYears($offset),
            default => $date->addDays($offset),
        };
    }

    private function describeOffset(int $offset, string $unit): string
    {
        $amount = abs($offset);
        $label = $amount === 1 ? rtrim($unit, 's') : $unit;

        return ($offset < 0 ? '-' : '+')."{$amount} {$label}";
    }
}
