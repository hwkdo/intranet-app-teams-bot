<?php

use function Livewire\Volt\title;

title('Hintergrund');

?>

<div>
    <x-intranet-app-teams-bot::teams-bot-layout heading="Hintergrund" subheading="Geplante Tasks, Jobs, Mails und Benachrichtigungen">
        @livewire('intranet-app-base::background-activities', ['appIdentifier' => 'teams-bot'])
    </x-intranet-app-teams-bot::teams-bot-layout>
</div>
