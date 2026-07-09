<?php

use function Livewire\Volt\{title};

title('TeamsBot - Meine Einstellungen');

?>

<div>
<x-intranet-app-teams-bot::teams-bot-layout heading="Meine Einstellungen" subheading="Persönliche Einstellungen für die TeamsBot App">
    @livewire('intranet-app-base::user-settings', ['appIdentifier' => 'teams-bot'])
</x-intranet-app-teams-bot::teams-bot-layout>
</div>
