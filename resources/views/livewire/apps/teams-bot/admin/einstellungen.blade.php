<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams Bot – Einstellungen')] class extends Component
{
};
?>

<div>
<x-intranet-app-teams-bot::teams-bot-layout heading="Teams Bot" subheading="Einstellungen">
    @livewire('intranet-app-base::admin-settings', [
        'appIdentifier' => 'teams-bot',
        'settingsModelClass' => '\Hwkdo\IntranetAppTeamsBot\Models\IntranetAppTeamsBotSettings',
        'appSettingsClass' => '\Hwkdo\IntranetAppTeamsBot\Data\AppSettings',
    ])
</x-intranet-app-teams-bot::teams-bot-layout>
</div>
