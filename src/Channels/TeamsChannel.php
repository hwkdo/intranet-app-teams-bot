<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Channels;

use Hwkdo\IntranetAppTeamsBot\Services\TeamsBotMessagingService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

class TeamsChannel
{
    public function __construct(
        private readonly TeamsBotMessagingService $messagingService,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTeams')) {
            return;
        }

        if (! config('intranet-app-teams-bot.bot.enabled')) {
            return;
        }

        $azureUserId = method_exists($notifiable, 'routeNotificationForTeams')
            ? $notifiable->routeNotificationForTeams($notification)
            : ($notifiable->socialite_id ?? null);

        if (! is_string($azureUserId) || $azureUserId === '') {
            return;
        }

        $azureUserId = strtolower($azureUserId);
        $message = $notification->toTeams($notifiable);

        if (! is_array($message)) {
            return;
        }

        $text = trim((string) ($message['preview'] ?? $message['body'] ?? ''));
        $card = $message['card'] ?? null;

        if (! is_array($card) && $text === '') {
            return;
        }

        try {
            $this->messagingService->queueMessage(
                $azureUserId,
                $text !== '' ? $text : 'Benachrichtigung',
                is_array($card) ? $card : null,
            );
        } catch (Throwable $exception) {
            Log::error('Teams-Bot-Notification fehlgeschlagen', [
                'azure_user_id' => $azureUserId,
                'notification' => $notification::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
