<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Jobs;

use Hwkdo\MsGraphLaravel\Services\TeamsActivityNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTeamsActivityFeedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $azureUserId,
        public readonly string $previewText,
        public readonly ?string $actorText = null,
        public readonly ?string $topicTitle = null,
        public readonly ?string $webUrl = null,
    ) {}

    public function handle(TeamsActivityNotificationService $notificationService): void
    {
        try {
            $notificationService->sendNotificationSync(
                $this->azureUserId,
                $this->previewText,
                $this->actorText,
                $this->topicTitle,
                $this->webUrl,
                config('intranet-app-teams-bot.bot.teams_app_id'),
                config('intranet-app-teams-bot.activity_feed.activity_type'),
            );
        } catch (Throwable $exception) {
            Log::error('SendTeamsActivityFeedNotificationJob fehlgeschlagen', [
                'azure_user_id' => $this->azureUserId,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
