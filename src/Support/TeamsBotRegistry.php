<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Support;

use Hwkdo\IntranetAppTeamsBot\Data\TeamsBotProfile;
use InvalidArgumentException;

class TeamsBotRegistry
{
    public function get(string $key): TeamsBotProfile
    {
        return match ($key) {
            'intranet' => $this->intranet(),
            'hermes' => $this->hermes(),
            default => throw new InvalidArgumentException("Unbekanntes Bot-Profil: {$key}"),
        };
    }

    public function has(string $key): bool
    {
        return in_array($key, ['intranet', 'hermes'], true);
    }

    /**
     * @return list<TeamsBotProfile>
     */
    public function selectable(): array
    {
        return array_values(array_filter(
            [$this->intranet(), $this->hermes()],
            fn (TeamsBotProfile $profile): bool => $profile->isSelectable(),
        ));
    }

    public function intranet(): TeamsBotProfile
    {
        $teamsAppId = config('intranet-app-teams-bot.bot.teams_app_id');

        return new TeamsBotProfile(
            key: 'intranet',
            label: 'Intranet-Bot',
            enabled: (bool) config('intranet-app-teams-bot.bot.enabled'),
            teamsAppId: is_string($teamsAppId) && $teamsAppId !== '' ? $teamsAppId : null,
            graphRegistration: (string) config('intranet-app-teams-bot.bot.graph_registration', 'teams_bot'),
            selfService: true,
            managesMessaging: true,
        );
    }

    public function hermes(): TeamsBotProfile
    {
        $teamsAppId = config('intranet-app-teams-bot.hermes.teams_app_id');

        return new TeamsBotProfile(
            key: 'hermes',
            label: (string) config('intranet-app-teams-bot.hermes.label', 'Hermes'),
            enabled: (bool) config('intranet-app-teams-bot.hermes.enabled'),
            teamsAppId: is_string($teamsAppId) && $teamsAppId !== '' ? $teamsAppId : null,
            graphRegistration: (string) config('intranet-app-teams-bot.hermes.graph_registration', 'teams_bot'),
            selfService: false,
            managesMessaging: false,
        );
    }
}
