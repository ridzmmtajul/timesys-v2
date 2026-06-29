<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate employees, keeping the earliest created record per (first_name, last_name).
        DB::statement("
            DELETE e FROM employees e
            INNER JOIN employees e2
                ON e.first_name = e2.first_name
               AND e.last_name  = e2.last_name
               AND e.created_at > e2.created_at
        ");

        Schema::table('employees', function (Blueprint $table) {
            $table->unique(['first_name', 'last_name'], 'employees_full_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique('employees_full_name_unique');
        });
    }
};
