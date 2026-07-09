@props([
    'heading' => '',
    'subheading' => '',
    'navItems' => [],
])

@php
    $defaultNavItems = [
        ['label' => 'Status', 'href' => route('apps.teams-bot.status'), 'icon' => 'signal', 'description' => 'Bot-Status und Conversations', 'buttonText' => 'Status anzeigen', 'permission' => 'manage-app-teams-bot'],
        ['label' => 'Benutzer', 'href' => route('apps.teams-bot.benutzer'), 'icon' => 'user', 'description' => 'Bot bei Benutzern installieren', 'buttonText' => 'Benutzer verwalten', 'permission' => 'manage-app-teams-bot'],
        ['label' => 'Kanäle', 'href' => route('apps.teams-bot.kanaele'), 'icon' => 'hashtag', 'description' => 'Team-Kanäle und Testnachrichten', 'buttonText' => 'Kanäle öffnen', 'permission' => 'manage-app-teams-bot'],
        ['label' => 'Gruppenchats', 'href' => route('apps.teams-bot.gruppenchats'), 'icon' => 'chat-bubble-left-right', 'description' => 'Gruppenchats verwalten', 'buttonText' => 'Gruppenchats öffnen', 'permission' => 'manage-app-teams-bot'],
        ['label' => 'Activity Feed', 'href' => route('apps.teams-bot.activity-feed'), 'icon' => 'bell-alert', 'description' => 'Activity-Feed-Benachrichtigungen', 'buttonText' => 'Activity Feed öffnen', 'permission' => 'manage-app-teams-bot'],
        ['label' => 'Einstellungen', 'href' => route('apps.teams-bot.admin.einstellungen'), 'icon' => 'cog-6-tooth', 'description' => 'App-Einstellungen verwalten', 'buttonText' => 'Einstellungen öffnen', 'permission' => 'manage-app-teams-bot'],
        ['label' => 'Hintergrundbild', 'href' => route('apps.teams-bot.admin.hintergrundbild'), 'icon' => 'photo', 'description' => 'App-Hintergrundbild anpassen', 'buttonText' => 'Hintergrundbild öffnen', 'permission' => 'manage-app-teams-bot'],
        ['label' => 'Meine Einstellungen', 'href' => route('apps.teams-bot.settings.user'), 'icon' => 'user-circle', 'description' => 'Persönliche Einstellungen', 'buttonText' => 'Einstellungen öffnen'],
        ['label' => 'App-Info', 'href' => route('apps.teams-bot.info'), 'icon' => 'information-circle', 'description' => 'Version und Release-Historie', 'buttonText' => 'App-Info anzeigen'],
    ];

    $navItems = ! empty($navItems) ? $navItems : $defaultNavItems;
    $customBgUrl = \Hwkdo\IntranetAppBase\Models\AppBackground::getCustomBackgroundUrl('teams-bot');
@endphp

@if($customBgUrl)
    @push('app-styles')
    <style data-app-bg data-ts="{{ uniqid() }}">
        :root { --app-bg-image: url('{{ $customBgUrl }}'); }
    </style>
    @endpush
@endif

<x-intranet-app-base::app-layout
    app-identifier="teams-bot"
    :heading="$heading"
    :subheading="$subheading"
    :nav-items="$navItems"
    :wrap-in-card="true"
>
    {{ $slot }}
</x-intranet-app-base::app-layout>
