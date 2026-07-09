<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Services;

use Hwkdo\IntranetAppTeamsBot\Interfaces\TeamsActivityFeedServiceInterface;
use Hwkdo\IntranetAppTeamsBot\Jobs\SendTeamsActivityFeedNotificationJob;
use Hwkdo\MsGraphLaravel\Services\TeamsActivityNotificationService;
use RuntimeException;

class TeamsActivityFeedService implements TeamsActivityFeedServiceInterface
{
    public function __construct(
        private readonly TeamsActivityNotificationService $notificationService,
    ) {}

    public function isEnabled(): bool
    {
        if (! config('intranet-app-teams-bot.activity_feed.enabled')) {
            return false;
        }

        $registration = (string) config(
            'intranet-app-teams-bot.activity_feed.graph_registration',
            'teams_bot',
        );

        $clientId = config('ms-graph-laravel.azure_app_registrations.'.$registration.'.client_id');
        $clientSecret = config('ms-graph-laravel.azure_app_registrations.'.$registration.'.client_secret');
        $teamsAppId = config('intranet-app-teams-bot.bot.teams_app_id');

        return filled($clientId)
            && filled($clientSecret)
            && filled($teamsAppId);
    }

    public function sendNotification(
        string $azureUserId,
        string $previewText,
        ?string $actorText = null,
        ?string $topicTitle = null,
        ?string $webUrl = null,
    ): void {
        SendTeamsActivityFeedNotificationJob::dispatch(
            $azureUserId,
            $previewText,
            $actorText,
            $topicTitle,
            $webUrl,
        );
    }

    public function sendNotificationSync(
        string $azureUserId,
        string $previewText,
        ?string $actorText = null,
        ?string $topicTitle = null,
        ?string $webUrl = null,
    ): void {
        if (! config('intranet-app-teams-bot.activity_feed.enabled')) {
            throw new RuntimeException('Teams Activity Feed ist deaktiviert.');
        }

        $this->notificationService->sendNotificationSync(
            $azureUserId,
            $previewText,
            $actorText,
            $topicTitle,
            $webUrl,
            config('intranet-app-teams-bot.bot.teams_app_id'),
            config('intranet-app-teams-bot.activity_feed.activity_type'),
        );
    }
}
