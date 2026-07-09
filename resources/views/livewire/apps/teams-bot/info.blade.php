<?php

use function Livewire\Volt\{title};

title('TeamsBot - App-Info');

?>

<div>
<x-intranet-app-teams-bot::teams-bot-layout heading="App-Info" subheading="Installierte Version und Release-Historie">
    @livewire('intranet-app-base::app-info', ['appIdentifier' => 'teams-bot'])
</x-intranet-app-teams-bot::teams-bot-layout>
</div>
