<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitor_targets', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->text('url');
            $table->string('method', 10)->default('GET');
            $table->unsignedInteger('interval_seconds')->default(60);
            $table->unsignedInteger('timeout_seconds')->default(10);
            $table->unsignedTinyInteger('failure_threshold')->default(2);
            $table->unsignedTinyInteger('success_threshold')->default(1);
            $table->json('expected_status')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('last_status', 20)->nullable();
            $table->dateTime('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitor_targets');
    }
};
