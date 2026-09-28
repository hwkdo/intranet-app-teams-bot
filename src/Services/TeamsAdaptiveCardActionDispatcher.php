<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Services;

use Hwkdo\IntranetAppTeamsBot\Data\TeamsAdaptiveCardAction;
use Hwkdo\IntranetAppTeamsBot\Interfaces\TeamsAdaptiveCardActionHandlerInterface;
use Hwkdo\IntranetAppTeamsBot\Support\TeamsAdaptiveCardInvokeResponse;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeamsAdaptiveCardActionDispatcher
{
    /**
     * @return array{statusCode: int, type: string, value: mixed}
     */
    public function dispatch(Authenticatable $user, TeamsAdaptiveCardAction $action): array
    {
        foreach ($this->handlers() as $handler) {
            if (! $handler->supports($action)) {
                continue;
            }

            try {
                return $handler->handle($user, $action);
            } catch (Throwable $exception) {
                Log::error('Teams Adaptive Card Action Handler fehlgeschlagen', [
                    'handler' => $handler::class,
                    'verb' => $action->verb,
                    'message' => $exception->getMessage(),
                ]);

                return TeamsAdaptiveCardInvokeResponse::message(
                    'Die Aktion ist fehlgeschlagen: '.$exception->getMessage(),
                );
            }
        }

        return TeamsAdaptiveCardInvokeResponse::message(
            'Für diese Aktion ist kein Handler registriert.',
        );
    }

    /**
     * @return list<TeamsAdaptiveCardActionHandlerInterface>
     */
    private function handlers(): array
    {
        $classes = config('intranet-app-teams-bot.adaptive_card_action_handlers', []);

        if (! is_array($classes)) {
            return [];
        }

        $handlers = [];

        foreach ($classes as $class) {
            if (! is_string($class) || $class === '' || ! class_exists($class)) {
                continue;
            }

            $handler = app($class);

            if ($handler instanceof TeamsAdaptiveCardActionHandlerInterface) {
                $handlers[] = $handler;
            }
        }

        return $handlers;
    }
}
