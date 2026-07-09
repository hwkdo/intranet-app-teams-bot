<?php

namespace Hwkdo\IntranetAppTeamsBot;
use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Illuminate\Support\Collection;

class IntranetAppTeamsBot implements IntranetAppInterface 
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
        return \Hwkdo\IntranetAppTeamsBot\Data\UserSettings::class;
    }
    
    public static function appSettingsClass(): ?string
    {
        return \Hwkdo\IntranetAppTeamsBot\Data\AppSettings::class;
    }

    public static function mcpServers(): array
    {
        return [];
    }
}
