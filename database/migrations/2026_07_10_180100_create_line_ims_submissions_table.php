<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_ims_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('line_chat_source_id')->constrained('line_chat_sources')->cascadeOnDelete();
            $table->foreignId('draft_issue_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->foreignId('submitted_issue_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->string('webhook_event_id')->nullable()->unique();
            $table->string('status');
            $table->text('error_message')->nullable();
            $table->json('form_state')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['line_chat_source_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_ims_submissions');
    }
};
