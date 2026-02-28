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
        Schema::create('sla_configurations', function (Blueprint $table) {
            $table->id();
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->smallInteger('acknowledge_within_hrs')->unsigned();
            $table->smallInteger('resolve_within_hrs')->unsigned();
            $table->timestamp('effective_from');
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_configurations');
    }
};
