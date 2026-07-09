<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Events;

use Hwkdo\IntranetAppTeamsBot\Data\TeamsBotIncomingMessage;
use Illuminate\Foundation\Events\Dispatchable;

class TeamsBotMessageReceived
{
    use Dispatchable;

    public function __construct(
        public readonly TeamsBotIncomingMessage $message,
    ) {}
}
