<?php

use Flux\Flux;
use Hwkdo\IntranetAppTeamsBot\Interfaces\TeamsBotServiceInterface;
use Hwkdo\MsGraphLaravel\Interfaces\MsGraphUserServiceInterface;
use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams Bot – Benutzer')] class extends Component
{
    public string $search = '';

    /** @var array<int, array{id: string, upn: string, displayName: string}> */
    public array $searchResults = [];

    /** @var array{id: string, upn: string, displayName: string}|null */
    public ?array $selectedUser = null;

    public string $testMessage = 'Dies ist eine Testnachricht vom HWK Intranet Teams-Bot.';

    public function updatedSearch(): void
    {
        if (strlen(trim($this->search)) < 2) {
            $this->searchResults = [];

            return;
        }

        $userService = app(MsGraphUserServiceInterface::class);
        $result = $userService->getUsersPaginated(20, trim($this->search));
        $users = $result['users'] ?? [];

        $this->searchResults = collect($users)
            ->map(fn ($user): array => [
                'id' => (string) $user->getId(),
                'upn' => (string) $user->getUserPrincipalName(),
                'displayName' => (string) ($user->getDisplayName() ?? ''),
            ])
            ->filter(fn (array $user): bool => $user['id'] !== '' && $user['upn'] !== '')
            ->values()
            ->all();
    }

    public function selectUser(string $upn, string $displayName, string $id): void
    {
        $this->selectedUser = [
            'id' => $id,
            'upn' => $upn,
            'displayName' => $displayName,
        ];
        $this->search = $displayName !== '' ? $displayName : $upn;
        $this->searchResults = [];
    }

    public function clearSelectedUser(): void
    {
        $this->selectedUser = null;
        $this->search = '';
        $this->searchResults = [];
    }

    public function installBot(): void
    {
        if ($this->selectedUser === null) {
            Flux::toast(variant: 'warning', text: 'Bitte zuerst einen Benutzer auswählen.');

            return;
        }

        try {
            app(TeamsBotServiceInterface::class)->installForUser(
                $this->selectedUser['id'],
                $this->selectedUser['upn'],
                $this->selectedUser['displayName'] !== '' ? $this->selectedUser['displayName'] : null,
            );

            Flux::toast(
                variant: 'success',
                text: 'Bot-Installation wurde gestartet. Bei Erfolg wird der Status automatisch aktualisiert.'
            );
        } catch (\Throwable $exception) {
            Flux::toast(variant: 'danger', text: 'Installation fehlgeschlagen: '.$exception->getMessage());
        }
    }

    public function sendTestMessage(): void
    {
        if ($this->selectedUser === null) {
            Flux::toast(variant: 'warning', text: 'Bitte zuerst einen Benutzer auswählen.');

            return;
        }

        $message = trim($this->testMessage);

        if ($message === '') {
            Flux::toast(variant: 'warning', text: 'Bitte eine Testnachricht eingeben.');

            return;
        }

        try {
            app(TeamsBotServiceInterface::class)->sendMessage(
                $this->selectedUser['id'],
                $message,
            );

            Flux::toast(variant: 'success', text: 'Testnachricht wurde in die Queue gestellt.');
        } catch (\Throwable $exception) {
            Flux::toast(variant: 'danger', text: 'Testnachricht fehlgeschlagen: '.$exception->getMessage());
        }
    }

    public function installAllUsers(): void
    {
        try {
            Artisan::call('teams-bot:install-all');

            Flux::toast(
                variant: 'success',
                text: 'Masseninstallation gestartet. Details siehe Queue/Logs.'
            );
        } catch (\Throwable $exception) {
            Flux::toast(variant: 'danger', text: 'Masseninstallation fehlgeschlagen: '.$exception->getMessage());
        }
    }
};
?>

<div>
<x-intranet-app-teams-bot::teams-bot-layout heading="Teams Bot" subheading="Benutzer">
    <flux:card class="glass-card">
        <flux:heading size="lg" class="mb-4">Benutzer & Testnachricht</flux:heading>

        <div class="space-y-4">
            <div class="relative">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    label="Entra-Benutzer suchen"
                    placeholder="Name oder UPN eingeben…"
                />

                @if($searchResults !== [])
                    <div class="absolute z-20 mt-1 w-full rounded-xl border border-[#d0e3f9] bg-white shadow-lg dark:border-white/10 dark:bg-[#04214e]">
                        @foreach($searchResults as $result)
                            <button
                                type="button"
                                class="block w-full px-4 py-3 text-left hover:bg-[#d0e3f9]/50 dark:hover:bg-white/5"
                                wire:click="selectUser(@js($result['upn']), @js($result['displayName']), @js($result['id']))"
                            >
                                <div class="font-medium">{{ $result['displayName'] ?: $result['upn'] }}</div>
                                <div class="text-xs text-zinc-500 dark:text-white/50">{{ $result['upn'] }}</div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            @if($selectedUser)
                <div class="flex items-center justify-between rounded-lg border border-[#d0e3f9]/80 dark:border-white/10 p-3">
                    <div>
                        <flux:text class="font-semibold">
                            {{ $selectedUser['displayName'] ?: $selectedUser['upn'] }}
                        </flux:text>
                        <flux:text class="text-sm text-zinc-500">{{ $selectedUser['upn'] }}</flux:text>
                    </div>
                    <flux:button variant="ghost" size="sm" wire:click="clearSelectedUser">Entfernen</flux:button>
                </div>
            @endif

            <flux:textarea
                wire:model="testMessage"
                label="Testnachricht"
                rows="3"
            />

            <div class="flex flex-wrap gap-2">
                <flux:button
                    wire:click="installBot"
                    wire:target="installBot"
                    wire:loading.attr="disabled"
                    icon="arrow-down-tray"
                    variant="primary"
                >
                    <span wire:loading.remove wire:target="installBot">Bot installieren</span>
                    <span wire:loading wire:target="installBot">Installiere…</span>
                </flux:button>

                <flux:button
                    wire:click="sendTestMessage"
                    wire:target="sendTestMessage"
                    wire:loading.attr="disabled"
                    icon="paper-airplane"
                >
                    <span wire:loading.remove wire:target="sendTestMessage">Testnachricht senden</span>
                    <span wire:loading wire:target="sendTestMessage">Sende…</span>
                </flux:button>

                <flux:button
                    wire:click="installAllUsers"
                    wire:target="installAllUsers"
                    wire:loading.attr="disabled"
                    icon="users"
                    variant="ghost"
                    wire:confirm="Bot-Installation für alle Entra-Benutzer starten?"
                >
                    <span wire:loading.remove wire:target="installAllUsers">Alle Benutzer installieren</span>
                    <span wire:loading wire:target="installAllUsers">Starte…</span>
                </flux:button>
            </div>
        </div>
    </flux:card>
</x-intranet-app-teams-bot::teams-bot-layout>
</div>
