<?php

namespace App\Ai\Tools;

use App\Ai\Time\CurrentDateTimeProvider;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetUserTimezone implements Tool
{
    public function __construct(
        private readonly CurrentDateTimeProvider $time,
        private readonly ?string $userTimezone = null,
    ) {}

    public function description(): Stringable|string
    {
        return 'Get the current user\'s IANA timezone identifier. Use this to answer questions that depend on the user\'s local time, such as "what time is it for me?". Returns an IANA identifier like "UTC" or "Asia/Karachi".';
    }

    public function handle(Request $request): Stringable|string
    {
        $timezone = $this->userTimezone ?: $this->time->defaultTimezone();

        return "The current user's timezone is: {$timezone}";
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
