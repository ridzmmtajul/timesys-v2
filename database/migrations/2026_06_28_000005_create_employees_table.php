<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('employee_no');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('name_ext')->nullable();
            $table->string('gender')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('job_title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->cascadeOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->cascadeOnDelete();
            $table->foreignId('office_division_id')->nullable()->constrained('office_divisions')->restrictOnDelete();
            $table->string('image')->nullable();
            $table->bigInteger('title_id')->nullable();
            $table->string('signature')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
