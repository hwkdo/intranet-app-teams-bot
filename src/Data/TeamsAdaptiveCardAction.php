<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Data;

class TeamsAdaptiveCardAction
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $activity
     * @param  array<string, mixed>  $conversationRef
     */
    public function __construct(
        public readonly string $azureUserId,
        public readonly string $verb,
        public readonly array $data,
        public readonly array $activity,
        public readonly array $conversationRef,
    ) {}
}
