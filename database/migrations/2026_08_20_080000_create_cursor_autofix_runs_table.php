<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursor_autofix_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->cascadeOnDelete();
            $table->string('status');
            $table->string('repo_key')->nullable();
            $table->string('repo_name')->nullable();
            $table->string('repo_path')->nullable();
            $table->string('matched_host')->nullable();
            $table->string('git_branch')->nullable();
            $table->text('prompt')->nullable();
            $table->json('command')->nullable();
            $table->longText('stdout')->nullable();
            $table->longText('stderr')->nullable();
            $table->integer('exit_code')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('dry_run')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['issue_id', 'status']);
            $table->index('repo_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursor_autofix_runs');
    }
};
