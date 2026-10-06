<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Enums;

enum TeamsCatalogInstallStatus: string
{
    case Pending = 'pending';
    case Installed = 'installed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Ausstehend',
            self::Installed => 'Installiert',
            self::Failed => 'Fehlgeschlagen',
        };
    }
}
