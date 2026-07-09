<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:manage-app-teams-bot'])->group(function (): void {
    Route::livewire('apps/teams-bot/status', 'intranet-app-teams-bot::apps.teams-bot.status')
        ->name('apps.teams-bot.status');
    Route::livewire('apps/teams-bot/benutzer', 'intranet-app-teams-bot::apps.teams-bot.benutzer')
        ->name('apps.teams-bot.benutzer');
    Route::livewire('apps/teams-bot/kanaele', 'intranet-app-teams-bot::apps.teams-bot.kanaele')
        ->name('apps.teams-bot.kanaele');
    Route::livewire('apps/teams-bot/gruppenchats', 'intranet-app-teams-bot::apps.teams-bot.gruppenchats')
        ->name('apps.teams-bot.gruppenchats');
    Route::livewire('apps/teams-bot/activity-feed', 'intranet-app-teams-bot::apps.teams-bot.activity-feed')
        ->name('apps.teams-bot.activity-feed');
    Route::livewire('apps/teams-bot/admin/einstellungen', 'intranet-app-teams-bot::apps.teams-bot.admin.einstellungen')
        ->name('apps.teams-bot.admin.einstellungen');
    Route::livewire('apps/teams-bot/admin/hintergrundbild', 'intranet-app-teams-bot::apps.teams-bot.admin.hintergrundbild')
        ->name('apps.teams-bot.admin.hintergrundbild');
});

Route::middleware(['web', 'auth', 'can:see-app-teams-bot'])->group(function (): void {
    Route::redirect('apps/teams-bot', '/apps/teams-bot/status')->name('apps.teams-bot.index');
    Route::livewire('apps/teams-bot/settings/user', 'intranet-app-teams-bot::apps.teams-bot.settings.user')
        ->name('apps.teams-bot.settings.user');
    Route::livewire('apps/teams-bot/info', 'intranet-app-teams-bot::apps.teams-bot.info')
        ->name('apps.teams-bot.info');
});
