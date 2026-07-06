<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->boolean('signature_valid');
            $table->string('destination')->nullable();
            $table->unsignedInteger('event_count')->default(0);
            $table->string('raw_body_hash', 64);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_webhook_logs');
    }
};
