<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Interfaces;

use Hwkdo\IntranetAppTeamsBot\Data\TeamsAdaptiveCardAction;
use Illuminate\Contracts\Auth\Authenticatable;

interface TeamsAdaptiveCardActionHandlerInterface
{
    public function supports(TeamsAdaptiveCardAction $action): bool;

    /**
     * @return array{statusCode: int, type: string, value: mixed}
     */
    public function handle(Authenticatable $user, TeamsAdaptiveCardAction $action): array;
}
