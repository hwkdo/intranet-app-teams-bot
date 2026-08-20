<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Channels;

use Hwkdo\IntranetAppTeamsBot\Interfaces\TeamsActivityFeedServiceInterface;
use Illuminate\Notifications\Notification;

class TeamsChannel
{
    public function __construct(
        private readonly TeamsActivityFeedServiceInterface $activityFeedService,
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTeams')) {
            return;
        }

        $azureUserId = method_exists($notifiable, 'routeNotificationForTeams')
            ? $notifiable->routeNotificationForTeams($notification)
            : ($notifiable->socialite_id ?? null);

        if (! is_string($azureUserId) || $azureUserId === '') {
            return;
        }

        if (! $this->activityFeedService->isEnabled()) {
            return;
        }

        $message = $notification->toTeams($notifiable);

        if (! is_array($message)) {
            return;
        }

        $this->activityFeedService->sendNotification(
            $azureUserId,
            (string) ($message['preview'] ?? $message['body'] ?? ''),
            isset($message['actor']) ? (string) $message['actor'] : null,
            isset($message['topic']) ? (string) $message['topic'] : null,
            isset($message['url']) ? (string) $message['url'] : null,
        );
    }
}
