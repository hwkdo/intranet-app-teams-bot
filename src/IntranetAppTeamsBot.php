<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot;

use Hwkdo\IntranetAppBase\Data\SetupDefinition;
use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Hwkdo\IntranetAppBase\Interfaces\ProvidesSetupInterface;
use Illuminate\Support\Collection;

class IntranetAppTeamsBot implements IntranetAppInterface, ProvidesSetupInterface
{
    public static function app_name(): string
    {
        return 'TeamsBot';
    }

    public static function app_icon(): string
    {
        return 'chat-bubble-left-right';
    }

    public static function identifier(): string
    {
        return 'teams-bot';
    }

    public static function roles_admin(): Collection
    {
        return collect(config('intranet-app-teams-bot.roles.admin'));
    }

    public static function roles_user(): Collection
    {
        return collect(config('intranet-app-teams-bot.roles.user'));
    }

    public static function userSettingsClass(): ?string
    {
        return null;
    }

    public static function appSettingsClass(): ?string
    {
        return \Hwkdo\IntranetAppTeamsBot\Data\AppSettings::class;
    }

    public static function mcpServers(): array
    {
        return [];
    }

    public static function setups(): array
    {
        return [
            new SetupDefinition(
                key: 'teams-bot',
                title: 'Teams-Benachrichtigungen einrichten',
                description: 'Teams-Bot installieren, damit Benachrichtigungen als 1:1-Chat zugestellt werden.',
                group: 'app',
                appIdentifier: self::identifier(),
                appName: self::app_name(),
                component: 'intranet-app-teams-bot::setup.user-setup',
                sort: 80,
            ),
        ];
    }
}
