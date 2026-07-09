<?php

use Flux\Flux;
use Hwkdo\IntranetAppTeamsBot\Interfaces\TeamsBotServiceInterface;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams Bot – Kanäle')] class extends Component
{
    public string $teamSearch = '';

    /** @var array<int, array{teamId: string, teamName: string}> */
    public array $teamSearchResults = [];

    /** @var array{teamId: string, teamName: string}|null */
    public ?array $selectedTeam = null;

    /** @var array<int, array{channelId: string, channelName: string}> */
    public array $teamChannels = [];

    public string $selectedChannelId = '';

    public string $channelTestMessage = 'Dies ist eine Testnachricht vom HWK Intranet Teams-Bot im Kanal.';

    public function updatedTeamSearch(): void
    {
        if (strlen(trim($this->teamSearch)) < 2) {
            $this->teamSearchResults = [];

            return;
        }

        try {
            $this->teamSearchResults = app(TeamsBotServiceInterface::class)
                ->searchTenantTeams(trim($this->teamSearch));
        } catch (\Throwable $exception) {
            $this->teamSearchResults = [];
            Flux::toast(variant: 'danger', text: 'Team-Suche fehlgeschlagen: '.$exception->getMessage());
        }
    }

    public function selectTeam(string $teamId, string $teamName): void
    {
        $this->selectedTeam = [
            'teamId' => $teamId,
            'teamName' => $teamName,
        ];
        $this->teamSearch = $teamName;
        $this->teamSearchResults = [];
        $this->selectedChannelId = '';
        $this->teamChannels = [];

        try {
            $this->teamChannels = app(TeamsBotServiceInterface::class)
                ->listTeamChannels($teamId);
        } catch (\Throwable $exception) {
            Flux::toast(variant: 'danger', text: 'Kanäle konnten nicht geladen werden: '.$exception->getMessage());
        }
    }

    public function clearSelectedTeam(): void
    {
        $this->selectedTeam = null;
        $this->teamSearch = '';
        $this->teamSearchResults = [];
        $this->teamChannels = [];
        $this->selectedChannelId = '';
    }

    public function installBotForTeam(): void
    {
        if ($this->selectedTeam === null) {
            Flux::toast(variant: 'warning', text: 'Bitte zuerst ein Team auswählen.');

            return;
        }

        try {
            app(TeamsBotServiceInterface::class)->installForTeam($this->selectedTeam['teamId']);

            Flux::toast(variant: 'success', text: 'Bot wurde im Team installiert bzw. auf die neueste Version aktualisiert.');
        } catch (\Throwable $exception) {
            Flux::toast(variant: 'danger', text: 'Team-Installation fehlgeschlagen: '.$exception->getMessage());
        }
    }

    public function sendChannelTestMessage(): void
    {
        if ($this->selectedTeam === null) {
            Flux::toast(variant: 'warning', text: 'Bitte zuerst ein Team auswählen.');

            return;
        }

        if (trim($this->selectedChannelId) === '') {
            Flux::toast(variant: 'warning', text: 'Bitte zuerst einen Kanal auswählen.');

            return;
        }

        $message = trim($this->channelTestMessage);

        if ($message === '') {
            Flux::toast(variant: 'warning', text: 'Bitte eine Testnachricht eingeben.');

            return;
        }

        try {
            app(TeamsBotServiceInterface::class)->sendChannelMessage(
                $this->selectedTeam['teamId'],
                $this->selectedChannelId,
                $message,
            );

            Flux::toast(variant: 'success', text: 'Kanal-Testnachricht wurde in die Queue gestellt.');
        } catch (\Throwable $exception) {
            Flux::toast(variant: 'danger', text: 'Kanal-Testnachricht fehlgeschlagen: '.$exception->getMessage());
        }
    }
};
?>

<div>
<x-intranet-app-teams-bot::teams-bot-layout heading="Teams Bot" subheading="Kanäle">
    <flux:card class="glass-card">
        <flux:heading size="lg" class="mb-4">Kanal & Testnachricht</flux:heading>

        <flux:callout class="mb-4" icon="information-circle" variant="secondary">
            Zuerst ein <span class="font-semibold">Team</span> suchen und auswählen, dann den
            <span class="font-semibold">Kanal</span> wählen. Der Bot muss im Team installiert sein,
            damit Kanal-Nachrichten zugestellt werden können.
        </flux:callout>

        <div class="space-y-4">
            <div class="relative">
                <flux:input
                    wire:model.live.debounce.500ms="teamSearch"
                    label="Team suchen"
                    placeholder="Team-Namen eingeben…"
                />

                <div wire:loading wire:target="teamSearch" class="absolute right-3 top-9">
                    <flux:icon icon="arrow-path" class="size-4 animate-spin text-zinc-400" />
                </div>

                @if($teamSearchResults !== [])
                    <div class="absolute z-20 mt-1 w-full max-h-64 overflow-y-auto rounded-xl border border-[#d0e3f9] bg-white shadow-lg dark:border-white/10 dark:bg-[#04214e]">
                        @foreach($teamSearchResults as $result)
                            <button
                                type="button"
                                class="block w-full px-4 py-3 text-left hover:bg-[#d0e3f9]/50 dark:hover:bg-white/5"
                                wire:click="selectTeam(@js($result['teamId']), @js($result['teamName']))"
                            >
                                <div class="font-medium">{{ $result['teamName'] }}</div>
                                <div class="text-xs font-mono text-zinc-500 dark:text-white/50">
                                    {{ $result['teamId'] }}
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($selectedTeam)
                <div class="flex items-center justify-between rounded-lg border border-[#d0e3f9]/80 dark:border-white/10 p-3">
                    <div>
                        <flux:text class="font-semibold">{{ $selectedTeam['teamName'] }}</flux:text>
                        <flux:text class="text-sm font-mono text-zinc-500">Team: {{ $selectedTeam['teamId'] }}</flux:text>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:button
                            wire:click="installBotForTeam"
                            wire:target="installBotForTeam"
                            wire:loading.attr="disabled"
                            size="sm"
                            icon="arrow-down-tray"
                        >
                            <span wire:loading.remove wire:target="installBotForTeam">Bot installieren / aktualisieren</span>
                            <span wire:loading wire:target="installBotForTeam">Läuft…</span>
                        </flux:button>
                        <flux:button variant="ghost" size="sm" wire:click="clearSelectedTeam">Entfernen</flux:button>
                    </div>
                </div>

                <flux:callout icon="information-circle" variant="secondary" class="text-sm">
                    Bei „BotNotInConversationRoster" bzw. 403 zuerst hier den Bot installieren/aktualisieren.
                </flux:callout>

                <flux:select
                    wire:model="selectedChannelId"
                    label="Kanal"
                    placeholder="Kanal auswählen…"
                >
                    @foreach($teamChannels as $channel)
                        <flux:select.option value="{{ $channel['channelId'] }}">
                            {{ $channel['channelName'] }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                @if($teamChannels === [])
                    <flux:text class="text-sm text-zinc-500">
                        Für dieses Team wurden keine Kanäle gefunden.
                    </flux:text>
                @endif
            @endif

            <flux:textarea
                wire:model="channelTestMessage"
                label="Kanal-Testnachricht"
                rows="3"
            />

            <div class="flex flex-wrap gap-2">
                <flux:button
                    wire:click="sendChannelTestMessage"
                    wire:target="sendChannelTestMessage"
                    wire:loading.attr="disabled"
                    icon="paper-airplane"
                    variant="primary"
                >
                    <span wire:loading.remove wire:target="sendChannelTestMessage">Testnachricht an Kanal senden</span>
                    <span wire:loading wire:target="sendChannelTestMessage">Sende…</span>
                </flux:button>
            </div>
        </div>
    </flux:card>
</x-intranet-app-teams-bot::teams-bot-layout>
</div>
