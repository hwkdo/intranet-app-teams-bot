<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTeamsBot\Models;

use Hwkdo\IntranetAppTeamsBot\Enums\TeamsCatalogInstallStatus;
use Hwkdo\IntranetAppTeamsBot\Enums\TeamsCatalogInstallTarget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TeamsBotInstallation extends Model
{
    protected $guarded = [];

    protected $table = 'intranet_app_teams_bot_installations';

    protected function casts(): array
    {
        return [
            'target_type' => TeamsCatalogInstallTarget::class,
            'status' => TeamsCatalogInstallStatus::class,
            'installed_at' => 'datetime',
        ];
    }

    /**
     * @return Collection<int, self>
     */
    public static function recentFor(string $botKey, int $limit = 8): Collection
    {
        return self::query()
            ->where('bot_key', $botKey)
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }
}
