<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->unique()->constrained('issues')->cascadeOnDelete();
            $table->text('root_cause');
            $table->json('steps_taken');
            $table->enum('resolution_type', ['temporary', 'permanent']);
            $table->foreignId('resolved_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('resolved_at');
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE resolutions ADD FULLTEXT INDEX ft_resolutions_root_cause (root_cause)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resolutions');
    }
};
