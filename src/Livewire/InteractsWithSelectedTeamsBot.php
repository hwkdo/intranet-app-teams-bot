<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Livewire;

use Hwkdo\IntranetAppTeamsBot\Data\TeamsBotProfile;
use Hwkdo\IntranetAppTeamsBot\Models\TeamsBotInstallation;
use Hwkdo\IntranetAppTeamsBot\Support\TeamsBotSelection;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;

trait InteractsWithSelectedTeamsBot
{
    #[Computed]
    public function selectedBot(): TeamsBotProfile
    {
        return TeamsBotSelection::profile();
    }

    /**
     * @return Collection<int, TeamsBotInstallation>
     */
    #[Computed]
    public function catalogInstallations(): Collection
    {
        if ($this->selectedBot->managesMessaging) {
            return collect();
        }

        return TeamsBotInstallation::recentFor($this->selectedBot->key);
    }

    protected function forgetCatalogInstallations(): void
    {
        unset($this->catalogInstallations);
    }
}
