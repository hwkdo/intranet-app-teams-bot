<?php

use Hwkdo\IntranetAppTeamsBot\Http\Controllers\TeamsWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('api/kunden/intranet-app-teams-bot/teams-webhook', TeamsWebhookController::class)
    ->name('intranet-app-teams-bot.teams-webhook');
