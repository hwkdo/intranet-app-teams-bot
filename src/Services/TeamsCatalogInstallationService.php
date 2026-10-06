<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Services;

use Hwkdo\IntranetAppTeamsBot\Data\TeamsBotProfile;
use Hwkdo\IntranetAppTeamsBot\Enums\TeamsCatalogInstallStatus;
use Hwkdo\IntranetAppTeamsBot\Enums\TeamsCatalogInstallTarget;
use Hwkdo\IntranetAppTeamsBot\Jobs\InstallHermesBotForUserJob;
use Hwkdo\IntranetAppTeamsBot\Models\TeamsBotInstallation;
use Hwkdo\IntranetAppTeamsBot\Support\TeamsBotRegistry;
use Hwkdo\MsGraphLaravel\Services\TeamsAppInstallationService;
use Hwkdo\MsGraphLaravel\Support\GraphExceptionMessage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TeamsCatalogInstallationService
{
    public function __construct(
        private readonly TeamsBotRegistry $registry,
    ) {}

    public function installForUser(string $azureUserId, string $upn, ?string $displayName = null): void
    {
        $this->assertCanInstall();
        $this->assertConfigured();

        InstallHermesBotForUserJob::dispatch($azureUserId, $upn, $displayName);
    }

    public function installForUserSync(string $azureUserId, string $upn, ?string $displayName = null): TeamsBotInstallation
    {
        $this->assertCanInstall();

        return $this->installQueuedUser($azureUserId, $upn, $displayName);
    }

    /**
     * Führt die Benutzer-Installation aus. Aufrufer ist die Queue, deshalb ohne erneute Rechteprüfung.
     */
    public function installQueuedUser(string $azureUserId, string $upn, ?string $displayName = null): TeamsBotInstallation
    {
        return $this->perform(
            TeamsCatalogInstallTarget::User,
            $azureUserId,
            $displayName ?? $upn,
            function (TeamsBotProfile $profile, TeamsAppInstallationService $installer) use ($azureUserId): void {
                $installer->installAppForUser($azureUserId, (string) $profile->teamsAppId);
            },
            fn (string $message): string => TeamsAppInstallationService::appendInstallationHint($message),
        );
    }

    public function installForTeam(string $teamId, ?string $displayName = null): TeamsBotInstallation
    {
        $this->assertCanInstall();

        return $this->perform(
            TeamsCatalogInstallTarget::Team,
            $teamId,
            $displayName,
            function (TeamsBotProfile $profile, TeamsAppInstallationService $installer) use ($teamId): void {
                $installer->installForTeamSync($teamId, (string) $profile->teamsAppId);
            },
            fn (string $message): string => TeamsAppInstallationService::appendTeamInstallationHint($message),
        );
    }

    public function installForChat(string $chatId, ?string $displayName = null): TeamsBotInstallation
    {
        $this->assertCanInstall();

        return $this->perform(
            TeamsCatalogInstallTarget::Chat,
            $chatId,
            $displayName,
            function (TeamsBotProfile $profile, TeamsAppInstallationService $installer) use ($chatId): void {
                $installer->installForChatSync($chatId, (string) $profile->teamsAppId);
            },
            fn (string $message): string => TeamsAppInstallationService::appendChatInstallationHint($message),
        );
    }

    /**
     * @param  callable(TeamsBotProfile, TeamsAppInstallationService): void  $install
     * @param  callable(string): string  $hint
     */
    private function perform(
        TeamsCatalogInstallTarget $targetType,
        string $targetId,
        ?string $displayName,
        callable $install,
        callable $hint,
    ): TeamsBotInstallation {
        $profile = $this->assertConfigured();

        $record = TeamsBotInstallation::query()->updateOrCreate(
            [
                'bot_key' => $profile->key,
                'target_type' => $targetType,
                'target_id' => $targetId,
            ],
            [
                'display_name' => $displayName,
                'status' => TeamsCatalogInstallStatus::Pending,
                'last_error' => null,
            ],
        );

        try {
            $install($profile, $this->installer($profile));

            $record->update([
                'status' => TeamsCatalogInstallStatus::Installed,
                'installed_at' => now(),
                'last_error' => null,
            ]);
        } catch (Throwable $exception) {
            if (TeamsAppInstallationService::isAlreadyInstalledError($exception)) {
                Log::info('Hermes-Katalog-App ist bereits installiert', [
                    'target_type' => $targetType->value,
                    'target_id' => $targetId,
                ]);

                $record->update([
                    'status' => TeamsCatalogInstallStatus::Installed,
                    'installed_at' => $record->installed_at ?? now(),
                    'last_error' => null,
                ]);

                return $record->fresh();
            }

            $message = $hint(GraphExceptionMessage::resolve(
                $exception,
                'Unbekannter Fehler bei der Bot-Installation.',
            ));

            $record->update([
                'status' => TeamsCatalogInstallStatus::Failed,
                'last_error' => $message,
            ]);

            Log::error('Hermes-Katalog-Installation fehlgeschlagen', [
                'target_type' => $targetType->value,
                'target_id' => $targetId,
                'message' => $message,
            ]);

            throw new RuntimeException($message, 0, $exception);
        }

        return $record->fresh();
    }

    private function installer(TeamsBotProfile $profile): TeamsAppInstallationService
    {
        $defaultRegistration = (string) config('intranet-app-teams-bot.bot.graph_registration', 'teams_bot');

        if ($profile->graphRegistration === $defaultRegistration) {
            return app(TeamsAppInstallationService::class);
        }

        return new TeamsAppInstallationService($profile->graphRegistration);
    }

    private function assertConfigured(): TeamsBotProfile
    {
        $profile = $this->registry->hermes();

        if (! $profile->enabled) {
            throw new RuntimeException('Hermes-Bot ist deaktiviert.');
        }

        if (! filled($profile->teamsAppId)) {
            throw new RuntimeException('MSGRAPH_HERMES_BOT_CATALOG_ID ist nicht konfiguriert.');
        }

        return $profile;
    }

    private function assertCanInstall(): void
    {
        $profile = $this->registry->hermes();

        if ($profile->selfService) {
            return;
        }

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return;
        }

        $user = auth()->user();

        if ($user !== null && $user->can('manage-app-teams-bot')) {
            return;
        }

        throw new AuthorizationException('Der Hermes-Bot kann nicht selbst eingerichtet werden.');
    }
}
