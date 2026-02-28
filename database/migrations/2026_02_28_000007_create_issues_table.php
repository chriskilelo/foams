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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('county_id')->constrained('counties')->restrictOnDelete();
            $table->string('issue_type');
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->enum('status', ['new', 'acknowledged', 'in_progress', 'pending_third_party', 'escalated', 'resolved', 'closed', 'duplicate'])->default('new');
            $table->enum('reporter_category', ['general_public', 'public_servant', 'field_officer']);
            $table->string('reporter_name')->nullable();
            $table->string('reporter_email')->nullable();
            $table->string('reporter_phone')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description');
            $table->boolean('workaround_applied')->default(false);
            $table->foreignId('duplicate_of_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->boolean('sla_breached')->default(false);
            $table->boolean('is_escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('escalated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['county_id', 'status'], 'idx_issues_county_status');
            $table->index(['severity', 'status'], 'idx_issues_severity_status');
            $table->index('sla_due_at', 'idx_issues_sla_due');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE issues ADD FULLTEXT INDEX ft_issues_description (description)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
