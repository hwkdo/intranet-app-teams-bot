<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ms_graph_laravel_teams_bot_conversations')) {
            return;
        }

        if (! Schema::hasTable('intranet_app_teams_bot_conversations')) {
            return;
        }

        $rows = DB::table('ms_graph_laravel_teams_bot_conversations')->get();

        foreach ($rows as $row) {
            DB::table('intranet_app_teams_bot_conversations')->updateOrInsert(
                ['azure_user_id' => $row->azure_user_id],
                [
                    'upn' => $row->upn,
                    'display_name' => $row->display_name,
                    'conversation_id' => $row->conversation_id,
                    'service_url' => $row->service_url,
                    'tenant_id' => $row->tenant_id,
                    'installed_at' => $row->installed_at,
                    'last_message_at' => $row->last_message_at,
                    'status' => $row->status,
                    'last_error' => $row->last_error,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ],
            );
        }
    }

    public function down(): void
    {
        // Datenmigration ist nicht reversibel ohne Datenverlust.
    }
};
