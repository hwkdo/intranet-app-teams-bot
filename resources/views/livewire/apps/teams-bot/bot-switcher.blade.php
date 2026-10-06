<?php

use Hwkdo\IntranetAppTeamsBot\Support\TeamsBotRegistry;
use Hwkdo\IntranetAppTeamsBot\Support\TeamsBotSelection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $botKey = 'intranet';

    public bool $intranetOnlyPage = false;

    public string $returnUrl = '';

    public function mount(): void
    {
        $this->botKey = TeamsBotSelection::key();
        $this->returnUrl = url()->current();
        $this->intranetOnlyPage = request()->routeIs(
            'apps.teams-bot.status',
            'apps.teams-bot.activity-feed',
            'apps.teams-bot.admin.index',
            'apps.teams-bot.admin.einstellungen',
            'apps.teams-bot.admin.hintergrundbild',
            'apps.teams-bot.admin.ki',
            'apps.teams-bot.info',
        );

        if (! TeamsBotSelection::profile()->managesMessaging && $this->intranetOnlyPage) {
            $this->redirect(route('apps.teams-bot.benutzer'));
        }
    }

    public function updatedBotKey(string $botKey): void
    {
        TeamsBotSelection::remember($botKey);

        $target = TeamsBotSelection::profile()->managesMessaging || ! $this->intranetOnlyPage
            ? $this->returnUrl
            : route('apps.teams-bot.benutzer');

        $this->redirect($target);
    }

    /**
     * @return list<\Hwkdo\IntranetAppTeamsBot\Data\TeamsBotProfile>
     */
    #[Computed]
    public function profiles(): array
    {
        return app(TeamsBotRegistry::class)->selectable();
    }
};
?>

<div>
    @if(count($this->profiles) > 1)
        <div class="mb-4 max-w-xs">
            <flux:select wire:model.live="botKey" label="Bot">
                @foreach($this->profiles as $profile)
                    <flux:select.option value="{{ $profile->key }}">{{ $profile->label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    @endif
</div>
