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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->uuid('business_id');
            $table->foreignId('issue_project_id')->nullable()->constrained('issue_projects')->nullOnDelete();
            $table->string('issue_number')->unique();
            $table->string('title');
            $table->text('url')->nullable();
            $table->enum('status', [
                'draft',
                'pending',
                'in_progress',
                'waiting_review',
                'customer_replied',
                'done',
            ])->default('pending');
            $table->string('priority')->default('medium');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('assigned_at')->nullable();
            $table->dateTime('planned_start_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('complete_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->cascadeOnDelete();
            $table->index(['business_id', 'status'], 'issues_business_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
