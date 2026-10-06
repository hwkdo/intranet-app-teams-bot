<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Support;

use Hwkdo\IntranetAppTeamsBot\Data\TeamsBotProfile;
use InvalidArgumentException;

class TeamsBotSelection
{
    public const SESSION_KEY = 'teams-bot.selected';

    public static function key(): string
    {
        $key = session(self::SESSION_KEY, 'intranet');

        if (! is_string($key)) {
            return 'intranet';
        }

        try {
            $profile = app(TeamsBotRegistry::class)->get($key);
        } catch (InvalidArgumentException) {
            return 'intranet';
        }

        if (! $profile->isSelectable()) {
            return 'intranet';
        }

        return $profile->key;
    }

    public static function profile(): TeamsBotProfile
    {
        return app(TeamsBotRegistry::class)->get(self::key());
    }

    public static function remember(string $key): void
    {
        $profile = app(TeamsBotRegistry::class)->get($key);

        if (! $profile->isSelectable()) {
            throw new InvalidArgumentException("Bot-Profil ist nicht auswählbar: {$key}");
        }

        session([self::SESSION_KEY => $profile->key]);
    }
}
