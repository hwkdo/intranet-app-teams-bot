<?php

use Flux\Flux;
use Hwkdo\IntranetAppTeamsBot\Interfaces\TeamsBotServiceInterface;
use Hwkdo\MsGraphLaravel\Interfaces\MsGraphUserServiceInterface;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams Bot – Gruppenchats')] class extends Component
{
    public string $chatUserSearch = '';

    /** @var array<int, array{id: string, upn: string, displayName: string}> */
    public array $chatUserSearchResults = [];

    /** @var array{id: string, upn: string, displayName: string}|null */
    public ?array $selectedChatUser = null;

    /** @var array<int, array{chatId: string, label: string}> */
    public array $groupChats = [];

    public string $selectedChatId = '';

    public string $chatTestMessage = 'Dies ist eine Testnachricht vom HWK Intranet Teams-Bot im Gruppenchat.';

    public function updatedChatUserSearch(): void
    {
        if (strlen(trim($this->chatUserSearch)) < 2) {
            $this->chatUserSearchResults = [];

            return;
        }

        $userService = app(MsGraphUserServiceInterface::class);
        $result = $userService->getUsersPaginated(20, trim($this->chatUserSearch));
        $users = $result['users'] ?? [];

        $this->chatUserSearchResults = collect($users)
            ->map(fn ($user): array => [
                'id' => (string) $user->getId(),
                'upn' => (string) $user->getUserPrincipalName(),
                'displayName' => (string) ($user->getDisplayName() ?? ''),
            ])
            ->filter(fn (array $user): bool => $user['id'] !== '' && $user['upn'] !== '')
            ->values()
            ->all();
    }

    public function selectChatUser(string $upn, string $displayName, string $id): void
    {
        $this->selectedChatUser = [
            'id' => $id,
            'upn' => $upn,
            'displayName' => $displayName,
        ];
        $this->chatUserSearch = $displayName !== '' ? $displayName : $upn;
        $this->chatUserSearchResults = [];
        $this->selectedChatId = '';
        $this->groupChats = [];

        try {
            $this->groupChats = app(TeamsBotServiceInterface::class)
                ->listUserGroupChats($id);

            if ($this->groupChats === []) {
                Flux::toast(variant: 'warning', text: 'Für diesen Benutzer wurden keine Gruppenchats gefunden.');
            }
        } catch (\Throwable $exception) {
            Flux::toast(variant: 'danger', text: 'Gruppenchats konnten nicht geladen werden: '.$exception->getMessage());
        }
    }

    public function clearSelectedChatUser(): void
    {
        $this->selectedChatUser = null;
        $this->chatUserSearch = '';
        $this->chatUserSearchResults = [];
        $this->groupChats = [];
        $this->selectedChatId = '';
    }

    public function installBotForChat(): void
    {
        if (trim($this->selectedChatId) === '') {
            Flux::toast(variant: 'warning', text: 'Bitte zuerst einen Gruppenchat auswählen.');

            return;
        }

        try {
            app(TeamsBotServiceInterface::class)->installForChat($this->selectedChatId);

            Flux::toast(variant: 'success', text: 'Bot wurde im Gruppenchat installiert bzw. auf die neueste Version aktualisiert.');
        } catch (\Throwable $exception) {
            Flux::toast(variant: 'danger', text: 'Gruppenchat-Installation fehlgeschlagen: '.$exception->getMessage());
        }
    }

    public function sendChatTestMessage(): void
    {
        if (trim($this->selectedChatId) === '') {
            Flux::toast(variant: 'warning', text: 'Bitte zuerst einen Gruppenchat auswählen.');

            return;
        }

        $message = trim($this->chatTestMessage);

        if ($message === '') {
            Flux::toast(variant: 'warning', text: 'Bitte eine Testnachricht eingeben.');

            return;
        }

        try {
            app(TeamsBotServiceInterface::class)->sendChatMessage($this->selectedChatId, $message);

            Flux::toast(variant: 'success', text: 'Gruppenchat-Testnachricht wurde in die Queue gestellt.');
        } catch (\Throwable $exception) {
            Flux::toast(variant: 'danger', text: 'Gruppenchat-Testnachricht fehlgeschlagen: '.$exception->getMessage());
        }
    }
};
?>

<div>
<x-intranet-app-teams-bot::teams-bot-layout heading="Teams Bot" subheading="Gruppenchats">
    <flux:card class="glass-card">
        <flux:heading size="lg" class="mb-4">Gruppenchat & Testnachricht</flux:heading>

        <flux:callout class="mb-4" icon="information-circle" variant="secondary">
            Gruppenchats haben oft keinen Namen, daher werden sie über einen
            <span class="font-semibold">Teilnehmer</span> gesucht: Benutzer auswählen, dann dessen
            Gruppenchats laden. Der Bot muss im Gruppenchat installiert sein.
        </flux:callout>

        <div class="space-y-4">
            <div class="relative">
                <flux:input
                    wire:model.live.debounce.300ms="chatUserSearch"
                    label="Teilnehmer suchen"
                    placeholder="Name oder UPN eingeben…"
                />

                @if($chatUserSearchResults !== [])
                    <div class="absolute z-20 mt-1 w-full max-h-64 overflow-y-auto rounded-xl border border-[#d0e3f9] bg-white shadow-lg dark:border-white/10 dark:bg-[#04214e]">
                        @foreach($chatUserSearchResults as $result)
                            <button
                                type="button"
                                class="block w-full px-4 py-3 text-left hover:bg-[#d0e3f9]/50 dark:hover:bg-white/5"
                                wire:click="selectChatUser(@js($result['upn']), @js($result['displayName']), @js($result['id']))"
                            >
                                <div class="font-medium">{{ $result['displayName'] ?: $result['upn'] }}</div>
                                <div class="text-xs text-zinc-500 dark:text-white/50">{{ $result['upn'] }}</div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($selectedChatUser)
                <div class="flex items-center justify-between rounded-lg border border-[#d0e3f9]/80 dark:border-white/10 p-3">
                    <div>
                        <flux:text class="font-semibold">
                            {{ $selectedChatUser['displayName'] ?: $selectedChatUser['upn'] }}
                        </flux:text>
                        <flux:text class="text-sm text-zinc-500">{{ $selectedChatUser['upn'] }}</flux:text>
                    </div>
                    <flux:button variant="ghost" size="sm" wire:click="clearSelectedChatUser">Entfernen</flux:button>
                </div>

                <flux:select
                    wire:model="selectedChatId"
                    label="Gruppenchat"
                    placeholder="Gruppenchat auswählen…"
                >
                    @foreach($groupChats as $chat)
                        <flux:select.option value="{{ $chat['chatId'] }}">
                            {{ $chat['label'] }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                @if($groupChats === [])
                    <flux:text class="text-sm text-zinc-500">
                        Für diesen Benutzer wurden keine Gruppenchats gefunden.
                    </flux:text>
                @endif
            @endif

            <flux:textarea
                wire:model="chatTestMessage"
                label="Gruppenchat-Testnachricht"
                rows="3"
            />

            <div class="flex flex-wrap gap-2">
                <flux:button
                    wire:click="installBotForChat"
                    wire:target="installBotForChat"
                    wire:loading.attr="disabled"
                    icon="arrow-down-tray"
                >
                    <span wire:loading.remove wire:target="installBotForChat">Bot installieren / aktualisieren</span>
                    <span wire:loading wire:target="installBotForChat">Läuft…</span>
                </flux:button>

                <flux:button
                    wire:click="sendChatTestMessage"
                    wire:target="sendChatTestMessage"
                    wire:loading.attr="disabled"
                    icon="paper-airplane"
                    variant="primary"
                >
                    <span wire:loading.remove wire:target="sendChatTestMessage">Testnachricht an Gruppenchat senden</span>
                    <span wire:loading wire:target="sendChatTestMessage">Sende…</span>
                </flux:button>
            </div>
        </div>
    </flux:card>
</x-intranet-app-teams-bot::teams-bot-layout>
</div>
