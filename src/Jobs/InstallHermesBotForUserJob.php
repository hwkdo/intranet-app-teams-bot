<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Jobs;

use Hwkdo\IntranetAppTeamsBot\Services\TeamsCatalogInstallationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class InstallHermesBotForUserJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $azureUserId,
        public readonly string $upn,
        public readonly ?string $displayName = null,
    ) {}

    public function handle(TeamsCatalogInstallationService $installationService): void
    {
        try {
            $installationService->installQueuedUser(
                $this->azureUserId,
                $this->upn,
                $this->displayName,
            );
        } catch (Throwable $exception) {
            Log::error('InstallHermesBotForUserJob fehlgeschlagen', [
                'azure_user_id' => $this->azureUserId,
                'upn' => $this->upn,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
