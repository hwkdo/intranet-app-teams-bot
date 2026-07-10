<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Services;

use Hwkdo\IntranetAppBase\Contracts\IntranetAiGatewayInterface;
use Hwkdo\IntranetAppBase\Data\AiRequestContext;
use Hwkdo\IntranetAppBase\Enums\AiCapability;
use Hwkdo\IntranetAppTeamsBot\Data\TeamsBotIncomingMessage;
use Hwkdo\IntranetAppTeamsBot\Models\IntranetAppTeamsBotSettings;
use Hwkdo\IntranetAppTeamsBot\Support\TeamsAiCommand;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeamsAiChatService
{
    public function __construct(
        private readonly IntranetAiGatewayInterface $gateway,
    ) {}

    public function answer(TeamsBotIncomingMessage $message): string
    {
        $settings = IntranetAppTeamsBotSettings::resolvedAppSettings();

        if (! $settings->aiChatEnabled) {
            return '';
        }

        $trigger = trim($settings->aiChatTriggerPhrase) !== ''
            ? $settings->aiChatTriggerPhrase
            : TeamsAiCommand::DEFAULT_TRIGGER;

        $prompt = TeamsAiCommand::extractPrompt($message, $trigger);

        if ($prompt === null) {
            return $settings->aiChatMissingPromptMessage;
        }

        try {
            $messages = [];

            if (trim($settings->aiChatSystemPrompt) !== '') {
                $messages[] = [
                    'role' => 'system',
                    'content' => $settings->aiChatSystemPrompt,
                ];
            }

            $messages[] = [
                'role' => 'user',
                'content' => $prompt,
            ];

            $result = $this->gateway->chat(
                $messages,
                new AiRequestContext(
                    appIdentifier: 'teams-bot',
                    capability: AiCapability::Text,
                    userId: $this->resolveUserId($message),
                ),
            );

            $content = trim($result->content);

            return $content !== '' ? $content : $settings->aiChatErrorMessage;
        } catch (Throwable $exception) {
            Log::error('Teams Bot KI-Antwort fehlgeschlagen', [
                'azure_user_id' => $message->azureUserId,
                'message_id' => $message->messageId,
                'error' => $exception->getMessage(),
            ]);

            return $settings->aiChatErrorMessage;
        }
    }

    private function resolveUserId(TeamsBotIncomingMessage $message): ?int
    {
        if (! filled($message->upn)) {
            return null;
        }

        $userModel = config('auth.providers.users.model');

        if (! is_string($userModel) || ! class_exists($userModel)) {
            return null;
        }

        $user = $userModel::query()
            ->where('email', $message->upn)
            ->first();

        return $user?->id;
    }
}
