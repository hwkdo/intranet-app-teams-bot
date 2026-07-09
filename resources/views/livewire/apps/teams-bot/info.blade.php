<?php

use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Teams Bot – App-Info')] class extends Component
{
};
?>

<div>
<x-intranet-app-teams-bot::teams-bot-layout heading="Teams Bot" subheading="App-Info">
    @livewire('intranet-app-base::app-info', ['appIdentifier' => 'teams-bot'])
</x-intranet-app-teams-bot::teams-bot-layout>
</div>
