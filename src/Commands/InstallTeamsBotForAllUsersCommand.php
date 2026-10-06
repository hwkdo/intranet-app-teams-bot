<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Commands;

use Hwkdo\IntranetAppTeamsBot\Data\TeamsBotProfile;
use Hwkdo\IntranetAppTeamsBot\Services\TeamsBotInstallationService;
use Hwkdo\IntranetAppTeamsBot\Services\TeamsCatalogInstallationService;
use Hwkdo\IntranetAppTeamsBot\Support\TeamsBotRegistry;
use Hwkdo\MsGraphLaravel\Interfaces\MsGraphUserServiceInterface;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

class InstallTeamsBotForAllUsersCommand extends Command
{
    protected $signature = 'teams-bot:install-all
                            {bot=intranet : Bot-Profil, intranet oder hermes}
                            {--top=100 : Anzahl Benutzer pro Graph-Seite}
                            {--search= : Optionaler Suchfilter für Benutzer}';

    protected $description = 'Installiert den Teams Bot für alle Entra-Benutzer (paginiert)';

    public function handle(
        MsGraphUserServiceInterface $userService,
        TeamsBotInstallationService $installationService,
        TeamsCatalogInstallationService $catalogInstallationService,
        TeamsBotRegistry $registry,
    ): int {
        try {
            $profile = $registry->get((string) $this->argument('bot'));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if (! $profile->enabled) {
            $this->error($profile->managesMessaging
                ? 'Teams Bot ist deaktiviert (MSGRAPH_TEAMS_BOT_ENABLED=false).'
                : $profile->label.' ist deaktiviert.');

            return self::FAILURE;
        }

        if (! $profile->managesMessaging && ! filled($profile->teamsAppId)) {
            $this->error('MSGRAPH_HERMES_BOT_CATALOG_ID ist nicht konfiguriert.');

            return self::FAILURE;
        }

        $top = max(1, (int) $this->option('top'));
        $search = $this->option('search');
        $nextLink = null;
        $installed = 0;
        $failed = 0;

        do {
            $result = $userService->getUsersPaginated($top, is_string($search) && $search !== '' ? $search : null, $nextLink);
            $users = $result['users'] ?? [];
            $nextLink = $result['nextLink'] ?? null;

            foreach ($users as $user) {
                $azureUserId = $user->getId();
                $upn = $user->getUserPrincipalName();
                $displayName = $user->getDisplayName();

                if (! is_string($azureUserId) || ! is_string($upn) || $upn === '') {
                    continue;
                }

                try {
                    $this->queueInstall(
                        $profile,
                        $installationService,
                        $catalogInstallationService,
                        $azureUserId,
                        $upn,
                        is_string($displayName) ? $displayName : null,
                    );
                    $installed++;
                    $this->line("Queued: {$upn}");
                } catch (Throwable $exception) {
                    $failed++;
                    $this->warn("Fehler für {$upn}: ".$exception->getMessage());
                }
            }
        } while (is_string($nextLink) && $nextLink !== '');

        $this->info("Installation gequeued: {$installed}, Fehler: {$failed}");

        return self::SUCCESS;
    }

    private function queueInstall(
        TeamsBotProfile $profile,
        TeamsBotInstallationService $installationService,
        TeamsCatalogInstallationService $catalogInstallationService,
        string $azureUserId,
        string $upn,
        ?string $displayName,
    ): void {
        if ($profile->managesMessaging) {
            $installationService->installForUser($azureUserId, $upn, $displayName);

            return;
        }

        $catalogInstallationService->installForUser($azureUserId, $upn, $displayName);
    }
}
