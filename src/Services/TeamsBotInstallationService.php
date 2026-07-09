<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Services;

use Hwkdo\IntranetAppTeamsBot\Enums\TeamsBotConversationStatus;
use Hwkdo\IntranetAppTeamsBot\Jobs\InstallTeamsBotForTeamJob;
use Hwkdo\IntranetAppTeamsBot\Jobs\InstallTeamsBotForUserJob;
use Hwkdo\IntranetAppTeamsBot\Models\TeamsBotConversation;
use Hwkdo\MsGraphLaravel\Services\TeamsAppInstallationService;
use Hwkdo\MsGraphLaravel\Support\GraphExceptionMessage;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TeamsBotInstallationService
{
    public function __construct(
        private readonly TeamsBotConversationResolver $conversationResolver,
        private readonly TeamsAppInstallationService $appInstallationService,
    ) {}

    public function installForUser(string $azureUserId, string $upn, ?string $displayName = null): void
    {
        if (! config('intranet-app-teams-bot.bot.enabled')) {
            throw new RuntimeException('Teams Bot ist deaktiviert.');
        }

        InstallTeamsBotForUserJob::dispatch($azureUserId, $upn, $displayName);
    }

    public function installForUserSync(string $azureUserId, string $upn, ?string $displayName = null): TeamsBotConversation
    {
        $teamsAppId = config('intranet-app-teams-bot.bot.teams_app_id');

        if (! filled($teamsAppId)) {
            throw new RuntimeException('MSGRAPH_TEAMS_APP_CATALOG_ID ist nicht konfiguriert.');
        }

        $conversation = TeamsBotConversation::query()->updateOrCreate(
            ['azure_user_id' => $azureUserId],
            [
                'upn' => $upn,
                'display_name' => $displayName,
                'status' => TeamsBotConversationStatus::Pending,
                'last_error' => null,
            ],
        );

        try {
            $this->appInstallationService->installAppForUser($azureUserId, (string) $teamsAppId);
            $this->finalizeInstallation($conversation, $azureUserId, $upn);
        } catch (Throwable $exception) {
            if (TeamsAppInstallationService::isAlreadyInstalledError($exception)) {
                Log::info('Teams Bot bereits installiert, Conversation wird aufgelöst', [
                    'azure_user_id' => $azureUserId,
                    'upn' => $upn,
                ]);
                $this->finalizeInstallation($conversation, $azureUserId, $upn);

                return $conversation->fresh();
            }

            $message = TeamsAppInstallationService::appendInstallationHint(
                GraphExceptionMessage::resolve(
                    $exception,
                    'Unbekannter Fehler bei der Bot-Installation.',
                ),
                config('intranet-app-teams-bot.bot.app_id'),
            );
            $conversation->markFailed($message);

            Log::error('Teams Bot Installation fehlgeschlagen', [
                'azure_user_id' => $azureUserId,
                'upn' => $upn,
                'message' => $message,
                'exception' => $exception::class,
            ]);

            throw new RuntimeException($message, 0, $exception);
        }

        return $conversation->fresh();
    }

    public function installForTeam(string $teamId): void
    {
        if (! config('intranet-app-teams-bot.bot.enabled')) {
            throw new RuntimeException('Teams Bot ist deaktiviert.');
        }

        InstallTeamsBotForTeamJob::dispatch($teamId);
    }

    public function installForTeamSync(string $teamId): void
    {
        $teamsAppId = config('intranet-app-teams-bot.bot.teams_app_id');

        if (! filled($teamsAppId)) {
            throw new RuntimeException('MSGRAPH_TEAMS_APP_CATALOG_ID ist nicht konfiguriert.');
        }

        $this->appInstallationService->installForTeamSync($teamId, (string) $teamsAppId);
    }

    public function installForChatSync(string $chatId): void
    {
        $teamsAppId = config('intranet-app-teams-bot.bot.teams_app_id');

        if (! filled($teamsAppId)) {
            throw new RuntimeException('MSGRAPH_TEAMS_APP_CATALOG_ID ist nicht konfiguriert.');
        }

        $this->appInstallationService->installForChatSync($chatId, (string) $teamsAppId);
    }

    private function finalizeInstallation(TeamsBotConversation $conversation, string $azureUserId, string $upn): void
    {
        $conversation->update([
            'status' => TeamsBotConversationStatus::Pending,
            'conversation_id' => null,
            'service_url' => null,
            'installed_at' => now(),
            'last_error' => null,
        ]);

        $conversation = $conversation->fresh();

        if (! $this->conversationResolver->resolve($conversation)) {
            Log::info('Teams Bot installiert, Conversation noch nicht auflösbar', [
                'azure_user_id' => $azureUserId,
                'upn' => $upn,
            ]);
        }
    }

    public function getInstallationStatus(string $azureUserId): ?TeamsBotConversation
    {
        return TeamsBotConversation::query()
            ->where('azure_user_id', $azureUserId)
            ->first();
    }
}
