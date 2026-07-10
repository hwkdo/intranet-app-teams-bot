<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Models;

use Hwkdo\IntranetAppTeamsBot\Data\AppSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class IntranetAppTeamsBotSettings extends Model
{
    protected $table = 'intranet_app_teams_bot_settings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => AppSettings::class.':default',
        ];
    }

    public static function current(): ?IntranetAppTeamsBotSettings
    {
        return static::query()
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();
    }

    public static function persistAppSettings(AppSettings $settings): IntranetAppTeamsBotSettings
    {
        $current = static::current();

        if ($current !== null) {
            $current->update(['settings' => $settings]);

            return $current->refresh();
        }

        return static::create([
            'version' => 1,
            'settings' => $settings,
        ]);
    }

    public static function resolvedAppSettings(): AppSettings
    {
        if (! Schema::hasTable((new static)->getTable())) {
            return new AppSettings;
        }

        $row = static::current();

        return $row?->settings instanceof AppSettings ? $row->settings : new AppSettings;
    }
}
