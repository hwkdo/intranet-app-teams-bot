<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Data;

final readonly class TeamsBotProfile
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $enabled,
        public ?string $teamsAppId,
        public string $graphRegistration,
        public bool $selfService,
        public bool $managesMessaging,
    ) {}

    public function isSelectable(): bool
    {
        if ($this->key === 'intranet') {
            return true;
        }

        return $this->enabled && filled($this->teamsAppId);
    }
}
