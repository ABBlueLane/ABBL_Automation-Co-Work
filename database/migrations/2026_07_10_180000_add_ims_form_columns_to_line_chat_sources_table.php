<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('line_chat_sources', function (Blueprint $table): void {
            $table->uuid('business_id')->nullable()->after('display_name');
            $table->string('form_type')->nullable()->after('business_id');
            $table->foreignId('draft_issue_id')->nullable()->after('form_type');
            $table->json('form_state')->nullable()->after('draft_issue_id');

            $table->index('business_id');
            $table->foreign('business_id')
                ->references('id')
                ->on('business')
                ->nullOnDelete();
            $table->foreign('draft_issue_id')
                ->references('id')
                ->on('issues')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('line_chat_sources', function (Blueprint $table): void {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['draft_issue_id']);
            $table->dropIndex(['business_id']);
            $table->dropColumn(['business_id', 'form_type', 'draft_issue_id', 'form_state']);
        });
    }
};
