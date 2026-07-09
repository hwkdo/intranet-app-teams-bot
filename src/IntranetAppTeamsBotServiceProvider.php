<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot;

use Hwkdo\IntranetAppTeamsBot\Commands\InstallTeamsBotForAllUsersCommand;
use Hwkdo\IntranetAppTeamsBot\Http\TeamsSdkRestClient;
use Hwkdo\IntranetAppTeamsBot\Interfaces\TeamsActivityFeedServiceInterface;
use Hwkdo\IntranetAppTeamsBot\Interfaces\TeamsBotServiceInterface;
use Hwkdo\IntranetAppTeamsBot\Services\TeamsActivityFeedService;
use Hwkdo\IntranetAppTeamsBot\Services\TeamsBotService;
use Hwkdo\MsGraphLaravel\Services\TeamsActivityNotificationBuilder;
use Hwkdo\MsGraphLaravel\Services\TeamsActivityNotificationService;
use Hwkdo\MsGraphLaravel\Services\TeamsAppInstallationService;
use Hwkdo\MsGraphLaravel\Services\TeamsDirectoryService;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IntranetAppTeamsBotServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('intranet-app-teams-bot')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(InstallTeamsBotForAllUsersCommand::class)
            ->discoversMigrations();
    }

    public function packageRegistered(): void
    {
        $graphRegistration = fn (): string => (string) config(
            'intranet-app-teams-bot.bot.graph_registration',
            'teams_bot',
        );

        $this->app->singleton(TeamsSdkRestClient::class);

        $this->app->bind(TeamsDirectoryService::class, function () use ($graphRegistration): TeamsDirectoryService {
            return new TeamsDirectoryService(graphRegistration: $graphRegistration());
        });

        $this->app->bind(TeamsAppInstallationService::class, function () use ($graphRegistration): TeamsAppInstallationService {
            return new TeamsAppInstallationService($graphRegistration());
        });

        $this->app->bind(TeamsActivityNotificationService::class, function () use ($graphRegistration): TeamsActivityNotificationService {
            return new TeamsActivityNotificationService(
                $this->app->make(TeamsActivityNotificationBuilder::class),
                (string) config(
                    'intranet-app-teams-bot.activity_feed.graph_registration',
                    $graphRegistration(),
                ),
            );
        });

        $this->app->bind(TeamsBotServiceInterface::class, TeamsBotService::class);
        $this->app->bind(TeamsActivityFeedServiceInterface::class, TeamsActivityFeedService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::addNamespace(
            namespace: 'intranet-app-teams-bot',
            viewPath: __DIR__.'/../resources/views/livewire',
            classNamespace: 'Hwkdo\IntranetAppTeamsBot\Livewire',
            classPath: __DIR__.'/Livewire',
            classViewPath: __DIR__.'/../resources/views/livewire',
        );

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
