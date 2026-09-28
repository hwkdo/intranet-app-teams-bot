<div class="space-y-4">
    <flux:text class="text-sm text-zinc-500">
        Teams-Benachrichtigungen werden als 1:1-Bot-Konversation zugestellt. Dafür muss der Teams-Bot für Ihr Konto installiert sein.
    </flux:text>

    @if(! config('intranet-app-teams-bot.bot.enabled'))
        <flux:callout variant="warning" icon="exclamation-triangle">
            Der Teams-Bot ist serverseitig deaktiviert.
        </flux:callout>
    @elseif(! $this->hasMicrosoftLogin)
        <flux:callout variant="warning" icon="exclamation-triangle">
            Bitte melden Sie sich einmal mit Microsoft an, bevor Teams eingerichtet werden kann.
        </flux:callout>
    @elseif($this->teamsAvailable)
        <flux:callout variant="success" icon="check-circle">
            Teams-Bot ist aktiv. Sie können den Kanal „Teams“ unter Benachrichtigungen einschalten.
        </flux:callout>
    @else
        <flux:callout variant="warning" icon="chat-bubble-left-right" class="mb-2">
            Teams ist noch nicht eingerichtet.
        </flux:callout>
        <flux:button
            type="button"
            wire:click="setupTeamsBot"
            wire:loading.attr="disabled"
            icon="chat-bubble-left-right"
        >
            Teams einrichten
        </flux:button>
    @endif
</div>
