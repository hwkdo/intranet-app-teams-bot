<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Enums;

enum TeamsCatalogInstallTarget: string
{
    case User = 'user';
    case Team = 'team';
    case Chat = 'chat';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Benutzer',
            self::Team => 'Team',
            self::Chat => 'Gruppenchat',
        };
    }
}
