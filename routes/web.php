<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['web','auth','can:see-app-teams-bot'])->group(function () {        
    Volt::route('apps/teams-bot', 'apps.teams-bot.index')->name('apps.teams-bot.index');
    Volt::route('apps/teams-bot/example', 'apps.teams-bot.example')->name('apps.teams-bot.example');
    Volt::route('apps/teams-bot/settings/user', 'apps.teams-bot.settings.user')->name('apps.teams-bot.settings.user');
    Volt::route('apps/teams-bot/info', 'apps.teams-bot.info')->name('apps.teams-bot.info');
});

Route::middleware(['web','auth','can:manage-app-teams-bot'])->group(function () {
    Volt::route('apps/teams-bot/admin', 'apps.teams-bot.admin.index')->name('apps.teams-bot.admin.index');
});
