<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_checks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('target_id')->constrained('monitor_targets')->cascadeOnDelete();
            $table->dateTime('checked_at');
            $table->boolean('ok');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error_message')->nullable();
            $table->string('probe_host')->nullable();
            $table->index(['target_id', 'checked_at'], 'monitor_checks_target_checked_at_idx');
            $table->index('checked_at', 'monitor_checks_checked_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_checks');
    }
};
