<?php

namespace Hwkdo\IntranetAppTeamsBot\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Hwkdo\IntranetAppTeamsBot\IntranetAppTeamsBot
 */
class IntranetAppTeamsBot extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Hwkdo\IntranetAppTeamsBot\IntranetAppTeamsBot::class;
    }
}
