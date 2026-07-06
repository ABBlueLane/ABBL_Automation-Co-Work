<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('line_chat_source_id')->constrained()->cascadeOnDelete();
            $table->string('webhook_event_id')->nullable()->unique();
            $table->string('reply_token')->nullable();
            $table->string('message_id')->nullable();
            $table->string('message_type');
            $table->text('text')->nullable();
            $table->string('sender_user_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('raw_event');
            $table->timestamps();

            $table->index(['line_chat_source_id', 'sent_at']);
            $table->index('message_id');
            $table->index('sender_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_chat_messages');
    }
};
