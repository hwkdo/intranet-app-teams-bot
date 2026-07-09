<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams Bot – Hintergrundbild')] class extends Component
{
};
?>

<div>
<x-intranet-app-teams-bot::teams-bot-layout heading="Teams Bot" subheading="Hintergrundbild">
    @livewire('intranet-app-base::app-background-image', ['appIdentifier' => 'teams-bot'])
</x-intranet-app-teams-bot::teams-bot-layout>
</div>
