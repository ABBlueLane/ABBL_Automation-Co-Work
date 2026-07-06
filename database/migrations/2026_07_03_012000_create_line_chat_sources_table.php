<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_chat_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type');
            $table->string('source_id');
            $table->string('display_name')->nullable();
            $table->boolean('is_collecting')->default(false);
            $table->string('started_by_user_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->string('stopped_by_user_id')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_chat_sources');
    }
};
