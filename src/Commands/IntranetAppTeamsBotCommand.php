<?php

namespace Hwkdo\IntranetAppTeamsBot\Commands;

use Illuminate\Console\Command;

class IntranetAppTeamsBotCommand extends Command
{
    public $signature = 'intranet-app-teams-bot';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
