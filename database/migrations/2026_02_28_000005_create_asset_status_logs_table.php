<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('logged_date');
            $table->time('observed_at')->nullable();
            $table->enum('status', ['operational', 'degraded', 'down', 'maintenance']);
            $table->decimal('throughput_mbps', 8, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_amendment')->default(false);
            $table->text('amendment_reason')->nullable();
            $table->timestamp('synced_at')->nullable()->comment('NULL means created offline');
            $table->timestamps();

            $table->unique(['asset_id', 'user_id', 'logged_date']);
            $table->index(['asset_id', 'logged_date'], 'idx_asl_asset_date');
            $table->index(['user_id', 'logged_date'], 'idx_asl_user_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_status_logs');
    }
};
