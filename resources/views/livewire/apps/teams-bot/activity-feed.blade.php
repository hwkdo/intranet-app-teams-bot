<?php

use Flux\Flux;
use Hwkdo\IntranetAppTeamsBot\Interfaces\TeamsActivityFeedServiceInterface;
use Hwkdo\MsGraphLaravel\Interfaces\MsGraphUserServiceInterface;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams Bot – Activity Feed')] class extends Component
{
    public string $search = '';

    /** @var array<int, array{id: string, upn: string, displayName: string}> */
    public array $searchResults = [];

    /** @var array{id: string, upn: string, displayName: string}|null */
    public ?array $selectedUser = null;

    public string $previewText = 'Dies ist eine Test-Benachrichtigung im Teams Activity Feed.';

    public string $actorText = 'HWKDO Intranet hat eine neue Benachrichtigung für dich.';

    public string $topicTitle = '';

    public string $webUrl = '';

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

    #[Computed]
    public function activityFeedEnabled(): bool
    {
        return app(TeamsActivityFeedServiceInterface::class)->isEnabled();
    }

    #[Computed]
    public function teamsAppId(): ?string
    {
        $appId = config('intranet-app-teams-bot.bot.teams_app_id');

        return is_string($appId) && $appId !== '' ? $appId : null;
    }

    #[Computed]
    public function activityType(): string
    {
        return (string) config('intranet-app-teams-bot.activity_feed.activity_type', 'systemDefault');
    }

    #[Computed]
    public function defaultTopicTitle(): string
    {
        return (string) config('intranet-app-teams-bot.activity_feed.topic_title', 'HWKDO Intranet');
    }

    public function sendTestNotification(): void
    {
        if ($this->selectedUser === null) {
            Flux::toast(variant: 'warning', text: 'Bitte zuerst einen Benutzer auswählen.');

            return;
        }

        $preview = trim($this->previewText);

        if ($preview === '') {
            Flux::toast(variant: 'warning', text: 'Bitte einen Vorschautext eingeben.');

            return;
        }

        try {
            app(TeamsActivityFeedServiceInterface::class)->sendNotification(
                $this->selectedUser['id'],
                $preview,
                filled(trim($this->actorText)) ? trim($this->actorText) : null,
                filled(trim($this->topicTitle)) ? trim($this->topicTitle) : null,
                filled(trim($this->webUrl)) ? trim($this->webUrl) : null,
            );

            Flux::toast(variant: 'success', text: 'Activity-Feed-Benachrichtigung wurde in die Queue gestellt.');
        } catch (\Throwable $exception) {
            Flux::toast(variant: 'danger', text: 'Senden fehlgeschlagen: '.$exception->getMessage());
        }
    }
};
?>

<div>
<x-intranet-app-teams-bot::teams-bot-layout heading="Teams Bot" subheading="Activity Feed">
    <div class="space-y-6">
        <flux:card class="glass-card">
            <flux:heading size="lg" class="mb-4">Teams Activity Feed Status</flux:heading>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-[#d0e3f9]/80 dark:border-white/10 bg-[#d0e3f9]/40 dark:bg-[#073070]/40 p-4">
                    <flux:text class="font-semibold">Aktiviert</flux:text>
                    <div class="mt-2">
                        @if($this->activityFeedEnabled)
                            <flux:badge color="green">Ja</flux:badge>
                        @else
                            <flux:badge color="zinc">Nein</flux:badge>
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border border-[#d0e3f9]/80 dark:border-white/10 bg-[#d0e3f9]/40 dark:bg-[#073070]/40 p-4">
                    <flux:text class="font-semibold">Teams App ID</flux:text>
                    <flux:text class="mt-2 font-mono text-sm break-all">
                        {{ $this->teamsAppId ?? '—' }}
                    </flux:text>
                </div>

                <div class="rounded-xl border border-[#d0e3f9]/80 dark:border-white/10 bg-[#d0e3f9]/40 dark:bg-[#073070]/40 p-4">
                    <flux:text class="font-semibold">Activity Type</flux:text>
                    <flux:text class="mt-2 font-mono text-sm">
                        {{ $this->activityType }}
                    </flux:text>
                </div>
            </div>

            <flux:callout class="mt-4" icon="information-circle" variant="secondary">
                Die Teams-App muss beim Empfänger installiert sein (z. B. über „Benutzer“).
                Erforderliche Graph-Berechtigung: <span class="font-mono">TeamsActivity.Send</span> oder
                <span class="font-mono">TeamsActivity.Send.User</span>.
            </flux:callout>
        </flux:card>

        <flux:card class="glass-card">
            <flux:heading size="lg" class="mb-4">Benutzer & Test-Benachrichtigung</flux:heading>

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

                <flux:input
                    wire:model="previewText"
                    label="Vorschautext"
                    description="Kurzer Text in der Benachrichtigung (max. 150 Zeichen)."
                />

                <flux:textarea
                    wire:model="actorText"
                    label="Actor-Text (systemDefaultText)"
                    rows="2"
                />

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input
                        wire:model="topicTitle"
                        label="Thema (optional)"
                        placeholder="{{ $this->defaultTopicTitle }}"
                    />

                    <flux:input
                        wire:model="webUrl"
                        label="Teams-Deep-Link (optional)"
                        placeholder="https://teams.microsoft.com/l/…"
                    />
                </div>

                <flux:button
                    wire:click="sendTestNotification"
                    wire:target="sendTestNotification"
                    wire:loading.attr="disabled"
                    icon="bell-alert"
                    variant="primary"
                >
                    <span wire:loading.remove wire:target="sendTestNotification">Test-Benachrichtigung senden</span>
                    <span wire:loading wire:target="sendTestNotification">Sende…</span>
                </flux:button>
            </div>
        </flux:card>
    </div>
</x-intranet-app-teams-bot::teams-bot-layout>
</div>
