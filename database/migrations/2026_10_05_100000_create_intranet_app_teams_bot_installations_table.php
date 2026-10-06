<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('intranet_app_teams_bot_installations')) {
            return;
        }

        Schema::create('intranet_app_teams_bot_installations', function (Blueprint $table) {
            $table->id();
            $table->string('bot_key');
            $table->string('target_type');
            $table->string('target_id');
            $table->string('display_name')->nullable();
            $table->string('status')->default('pending');
            $table->text('last_error')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();

            $table->unique(['bot_key', 'target_type', 'target_id'], 'iatb_install_target_uq');
            $table->index('status', 'iatb_install_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_app_teams_bot_installations');
    }
};
