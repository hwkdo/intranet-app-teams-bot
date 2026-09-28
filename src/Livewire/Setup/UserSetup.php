<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Livewire\Setup;

use Flux\Flux;
use Hwkdo\IntranetAppBase\Services\NotificationPreferenceResolver;
use Hwkdo\IntranetAppTeamsBot\Services\TeamsBotInstallationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

class UserSetup extends Component
{
    #[Computed]
    public function teamsAvailable(): bool
    {
        $user = Auth::user();

        return $user
            ? app(NotificationPreferenceResolver::class)->teamsAvailableFor($user)
            : false;
    }

    #[Computed]
    public function hasMicrosoftLogin(): bool
    {
        $user = Auth::user();

        return $user && filled($user->socialite_id ?? null);
    }

    public function setupTeamsBot(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        if (! (bool) config('intranet-app-teams-bot.bot.enabled', false)) {
            Flux::toast(
                heading: 'Teams nicht verfügbar',
                text: 'Der Teams-Bot ist serverseitig deaktiviert.',
                variant: 'warning',
            );

            return;
        }

        $azureUserId = $user->socialite_id ?? null;

        if (! is_string($azureUserId) || $azureUserId === '') {
            Flux::toast(
                heading: 'Microsoft-Anmeldung fehlt',
                text: 'Bitte melden Sie sich einmal mit Microsoft an.',
                variant: 'warning',
            );

            return;
        }

        $upn = filled($user->upn ?? null)
            ? (string) $user->upn
            : (string) ($user->email ?? '');

        if ($upn === '') {
            Flux::toast(
                heading: 'UPN fehlt',
                text: 'Für Ihr Konto konnte keine E-Mail/UPN ermittelt werden.',
                variant: 'warning',
            );

            return;
        }

        try {
            app(TeamsBotInstallationService::class)->installForUserSync(
                strtolower($azureUserId),
                $upn,
                is_string($user->name ?? null) ? $user->name : null,
            );

            unset($this->teamsAvailable);

            Flux::toast(
                heading: 'Teams-Installation gestartet',
                text: app(NotificationPreferenceResolver::class)->teamsAvailableFor($user)
                    ? 'Der Teams-Bot ist aktiv.'
                    : 'Bitte öffnen Sie kurz den Bot-Chat in Teams und kehren Sie danach hierher zurück.',
                variant: 'success',
            );
        } catch (Throwable $exception) {
            Flux::toast(
                heading: 'Teams-Einrichtung fehlgeschlagen',
                text: $exception->getMessage(),
                variant: 'danger',
            );
        }
    }

    public function render(): View
    {
        return view('intranet-app-teams-bot::livewire.setup.user-setup');
    }
}
