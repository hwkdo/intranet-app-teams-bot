<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Data;

use Hwkdo\IntranetAppBase\Contracts\HasAiSettings;
use Hwkdo\IntranetAppBase\Data\Attributes\Description;
use Hwkdo\IntranetAppBase\Data\BaseAppSettings;
use Hwkdo\IntranetAppBase\Traits\HasAiSettingsFields;

class AppSettings extends BaseAppSettings implements HasAiSettings
{
    use HasAiSettingsFields;

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
        public string $mentionHelpMessage = 'Du kannst mir z. B. schreiben: „frag die ki …" für eine KI-Antwort oder „@Bot erstelle mir ein Ticket, dass …", um ein Ticket zu erstellen.',

        #[Description('Aktiviert die „frag die ki"-Funktion im Teams Bot')]
        public bool $aiChatEnabled = true,

        #[Description('Auslöser-Phrase für KI-Anfragen (Groß-/Kleinschreibung egal)')]
        public string $aiChatTriggerPhrase = 'frag die ki',

        #[Description('System-Prompt für KI-Antworten im Teams Bot (leer = Standard)')]
        public string $aiChatSystemPrompt = 'Du bist ein hilfreicher Assistent für Mitarbeitende der Handwerkskammer. Antworte präzise, freundlich und auf Deutsch.',

        #[Description('Hinweis, wenn nach dem Auslöser keine Frage erkannt wurde')]
        public string $aiChatMissingPromptMessage = 'Bitte stelle nach „frag die ki" eine Frage oder zitiere die Nachricht, die ich beantworten soll.',

        #[Description('Fehlermeldung, wenn die KI-Anfrage fehlschlägt')]
        public string $aiChatErrorMessage = 'Entschuldigung, die KI konnte deine Anfrage gerade nicht beantworten. Bitte versuche es später erneut.',
    ) {}
}
