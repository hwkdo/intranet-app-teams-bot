<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Background;

use Hwkdo\IntranetAppBase\Data\BackgroundActivityDefinition;
use Hwkdo\IntranetAppTeamsBot\IntranetAppTeamsBot;
use Hwkdo\IntranetAppTeamsBot\Jobs\InstallHermesBotForUserJob;
use Hwkdo\IntranetAppTeamsBot\Jobs\InstallTeamsBotForTeamJob;
use Hwkdo\IntranetAppTeamsBot\Jobs\InstallTeamsBotForUserJob;
use Hwkdo\IntranetAppTeamsBot\Jobs\SendTeamsActivityFeedNotificationJob;
use Hwkdo\IntranetAppTeamsBot\Jobs\SendTeamsBotChannelMessageJob;
use Hwkdo\IntranetAppTeamsBot\Jobs\SendTeamsBotChatMessageJob;
use Hwkdo\IntranetAppTeamsBot\Jobs\SendTeamsBotMessageJob;

final class TeamsBotBackgroundActivities
{
    /**
     * @return list<BackgroundActivityDefinition>
     */
    public static function definitions(): array
    {
        $id = IntranetAppTeamsBot::identifier();
        $name = IntranetAppTeamsBot::app_name();

        return [
            BackgroundActivityDefinition::queued(key: 'teams-bot.install-user', label: 'Bot für Benutzer installieren', appIdentifier: $id, appName: $name, job: InstallTeamsBotForUserJob::class, sort: 10),
            BackgroundActivityDefinition::queued(key: 'teams-bot.install-team', label: 'Bot für Team installieren', appIdentifier: $id, appName: $name, job: InstallTeamsBotForTeamJob::class, sort: 20),
            BackgroundActivityDefinition::queued(key: 'teams-bot.install-hermes', label: 'Hermes-Bot installieren', appIdentifier: $id, appName: $name, job: InstallHermesBotForUserJob::class, sort: 30),
            BackgroundActivityDefinition::queued(key: 'teams-bot.activity-feed', label: 'Activity Feed', appIdentifier: $id, appName: $name, job: SendTeamsActivityFeedNotificationJob::class, sort: 40),
            BackgroundActivityDefinition::queued(key: 'teams-bot.channel-message', label: 'Kanalnachricht', appIdentifier: $id, appName: $name, job: SendTeamsBotChannelMessageJob::class, sort: 50),
            BackgroundActivityDefinition::queued(key: 'teams-bot.chat-message', label: 'Chatnachricht', appIdentifier: $id, appName: $name, job: SendTeamsBotChatMessageJob::class, sort: 60),
            BackgroundActivityDefinition::queued(key: 'teams-bot.message', label: 'Bot-Nachricht', appIdentifier: $id, appName: $name, job: SendTeamsBotMessageJob::class, sort: 70),
        ];
    }
}
