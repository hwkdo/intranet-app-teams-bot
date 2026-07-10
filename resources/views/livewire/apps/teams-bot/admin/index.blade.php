<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams Bot – Admin')] class extends Component
{
    public string $activeTab = 'einstellungen';

    public function mount(): void
    {
        $tab = request()->query('tab');

        if (is_string($tab) && in_array($tab, ['ki', 'einstellungen', 'hintergrundbild'], true)) {
            $this->activeTab = $tab;
        }
    }
};
?>

<div>
<x-intranet-app-teams-bot::teams-bot-layout heading="Teams Bot" subheading="Administration">
    <flux:tab.group>
        <flux:tabs wire:model.live="activeTab">
            <flux:tab name="ki" icon="sparkles">KI</flux:tab>
            <flux:tab name="einstellungen" icon="cog-6-tooth">Einstellungen</flux:tab>
            <flux:tab name="hintergrundbild" icon="photo">Hintergrundbild</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="ki">
            @if ($activeTab === 'ki')
                <div class="min-h-[400px]">
                    @livewire('intranet-app-teams-bot::apps.teams-bot.admin.ki-einstellungen', key('teams-bot-admin-ki'))
                </div>
            @endif
        </flux:tab.panel>

        <flux:tab.panel name="einstellungen">
            @if ($activeTab === 'einstellungen')
                <div class="min-h-[400px]">
                    @livewire('intranet-app-base::admin-settings', [
                        'appIdentifier' => 'teams-bot',
                        'settingsModelClass' => '\Hwkdo\IntranetAppTeamsBot\Models\IntranetAppTeamsBotSettings',
                        'appSettingsClass' => '\Hwkdo\IntranetAppTeamsBot\Data\AppSettings',
                        'excludedKeys' => [
                            'aiTextProviderOverride',
                            'aiTextModelOverride',
                            'aiImageProviderOverride',
                            'aiImageModelOverride',
                            'aiChatEnabled',
                            'aiChatTriggerPhrase',
                            'aiChatSystemPrompt',
                            'aiChatMissingPromptMessage',
                            'aiChatErrorMessage',
                        ],
                    ], key('teams-bot-admin-settings'))
                </div>
            @endif
        </flux:tab.panel>

        <flux:tab.panel name="hintergrundbild">
            @if ($activeTab === 'hintergrundbild')
                <div class="min-h-[400px]">
                    @livewire('intranet-app-base::app-background-image', [
                        'appIdentifier' => 'teams-bot',
                    ], key('teams-bot-admin-background'))
                </div>
            @endif
        </flux:tab.panel>
    </flux:tab.group>
</x-intranet-app-teams-bot::teams-bot-layout>
</div>
