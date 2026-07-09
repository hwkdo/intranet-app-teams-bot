<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams Bot – Meine Einstellungen')] class extends Component
{
};
?>

<div>
<x-intranet-app-teams-bot::teams-bot-layout heading="Teams Bot" subheading="Meine Einstellungen">
    @livewire('intranet-app-base::user-settings', ['appIdentifier' => 'teams-bot'])
</x-intranet-app-teams-bot::teams-bot-layout>
</div>
