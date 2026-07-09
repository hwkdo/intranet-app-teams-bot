<?php

namespace Hwkdo\IntranetAppTeamsBot\Models;

use Hwkdo\IntranetAppTeamsBot\Data\AppSettings;
use Illuminate\Database\Eloquent\Model;

class IntranetAppTeamsBotSettings extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => AppSettings::class.':default',
        ];
    }

    public static function current(): IntranetAppTeamsBotSettings|null
    {
        return self::orderBy('version', 'desc')->first();
    }
}
