<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('target_id')->constrained('monitor_targets')->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->enum('status', ['open', 'resolved'])->default('open');
            $table->unsignedSmallInteger('trigger_http_status')->nullable();
            $table->string('trigger_error')->nullable();
            $table->unsignedInteger('checks_failed_count')->default(0);
            $table->dateTime('notified_down_at')->nullable();
            $table->dateTime('notified_recovered_at')->nullable();
            $table->timestamps();

            $table->index(['target_id', 'started_at'], 'monitor_incidents_target_started_at_idx');
            $table->index('status', 'monitor_incidents_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_incidents');
    }
};
