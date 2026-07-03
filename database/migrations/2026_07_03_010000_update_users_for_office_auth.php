<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'name') && ! Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('id');
                $table->string('last_name')->nullable()->after('first_name');
                $table->string('nick_name')->nullable()->after('last_name');
            }

            if (! Schema::hasColumn('users', 'phone_no')) {
                $table->string('phone_no')->nullable()->after('remember_token');
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'inactive'])->default('active')->after('phone_no');
            }

            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        if (Schema::hasColumn('users', 'name') && Schema::hasColumn('users', 'first_name')) {
            DB::table('users')
                ->whereNull('first_name')
                ->orderBy('id')
                ->each(function ($user): void {
                    $parts = preg_split('/\s+/', trim((string) $user->name), 2) ?: [];

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update([
                            'first_name' => $parts[0] ?? (string) $user->name,
                            'last_name' => $parts[1] ?? '',
                        ]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['first_name', 'last_name', 'nick_name', 'phone_no', 'status', 'deleted_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
