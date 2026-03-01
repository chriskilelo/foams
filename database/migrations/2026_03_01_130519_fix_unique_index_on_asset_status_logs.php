<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replace the 3-column unique index with a 4-column one that includes
 * is_amendment. This allows one base log (is_amendment=false) AND one
 * amendment log (is_amendment=true) per asset per officer per day.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_status_logs', function (Blueprint $table) {
            $table->dropUnique(['asset_id', 'user_id', 'logged_date']);
            $table->unique(['asset_id', 'user_id', 'logged_date', 'is_amendment']);
        });
    }

    public function down(): void
    {
        Schema::table('asset_status_logs', function (Blueprint $table) {
            $table->dropUnique(['asset_id', 'user_id', 'logged_date', 'is_amendment']);
            $table->unique(['asset_id', 'user_id', 'logged_date']);
        });
    }
};
