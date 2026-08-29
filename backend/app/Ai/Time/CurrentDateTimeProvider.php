<?php

namespace App\Ai\Time;

use Carbon\CarbonImmutable;
use DateTimeZone;

class CurrentDateTimeProvider
{
    public function __construct(
        private readonly string $defaultTimezone,
    ) {}

    /**
     * Get the fallback timezone to use when none is supplied.
     */
    public function defaultTimezone(): string
    {
        return $this->defaultTimezone;
    }

    /**
     * Resolve a timezone string, falling back to the configured default.
     */
    public function resolveTimezone(?string $timezone): string
    {
        if (is_null($timezone) || $timezone === '') {
            return $this->defaultTimezone();
        }

        return $timezone;
    }

    /**
     * Get the current date and time, optionally in a given timezone.
     */
    public function now(?string $timezone = null): CarbonImmutable
    {
        return CarbonImmutable::now(new DateTimeZone($this->resolveTimezone($timezone)));
    }

    /**
     * Parse a date/time string into an immutable instance in the given timezone.
     */
    public function parse(string $value, ?string $timezone = null): CarbonImmutable
    {
        return CarbonImmutable::parse($value, new DateTimeZone($this->resolveTimezone($timezone)));
    }
}
