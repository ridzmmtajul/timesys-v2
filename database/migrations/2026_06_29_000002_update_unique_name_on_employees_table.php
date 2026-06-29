<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::select("
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'employees'
              AND index_name = 'employees_full_name_unique'
            LIMIT 1
        ");

        if ($exists) {
            DB::statement('ALTER TABLE employees DROP INDEX employees_full_name_unique');
        }

        // Use prefix lengths to stay within MySQL's 3072-byte key limit.
        DB::statement('ALTER TABLE employees ADD UNIQUE INDEX employees_full_name_unique (first_name(80), middle_name(80), last_name(80), name_ext(20))');
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_full_name_unique');
            $table->unique(['first_name', 'last_name'], 'employees_full_name_unique');
        });
    }
};
