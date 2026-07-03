<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'name')) {
            DB::statement('ALTER TABLE users MODIFY name VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'name')) {
            DB::statement("UPDATE users SET name = CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) WHERE name IS NULL");
            DB::statement('ALTER TABLE users MODIFY name VARCHAR(255) NOT NULL');
        }
    }
};
