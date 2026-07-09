<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Data;

use Hwkdo\IntranetAppBase\Data\Attributes\Description;
use Hwkdo\IntranetAppBase\Data\BaseAppSettings;

class AppSettings extends BaseAppSettings
{
    public function __construct(
        #[Description('Aktiviert den Teams Bot (überschreibt MSGRAPH_TEAMS_BOT_ENABLED wenn gesetzt)')]
        public ?bool $botEnabledOverride = null,

        #[Description('Aktiviert den Activity Feed (überschreibt MSGRAPH_TEAMS_ACTIVITY_FEED_ENABLED wenn gesetzt)')]
        public ?bool $activityFeedEnabledOverride = null,

        #[Description('Antwort auf „Hi“-Nachrichten')]
        public string $hiReplyMessage = 'Hallo! Schön, dass du da bist. Ich sende dir Benachrichtigungen aus dem HWKDO Intranet.',

        #[Description('Standard-Antwort auf nicht unterstützte Nachrichten')]
        public string $autoReplyMessage = 'Dies ist ein Benachrichtigungs-Bot. Bitte bearbeiten Sie Anfragen im Intranet.',

        #[Description('Hilfetext bei @-Erwähnung des Bots')]
        public string $mentionHelpMessage = 'Du kannst mir z. B. schreiben: „@Bot erstelle mir ein Ticket, dass …", um ein Ticket zu erstellen.',
    ) {}
}
