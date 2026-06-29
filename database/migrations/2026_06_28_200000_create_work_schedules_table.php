<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->char('employee_id', 36);
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('schedule_type_id')->nullable()->constrained('schedule_types')->cascadeOnDelete();
            $table->time('timein_AM')->nullable();
            $table->time('timeout_AM')->nullable();
            $table->time('timein_PM')->nullable();
            $table->time('timeout_PM')->nullable();
            $table->date('from_date')->nullable();
            $table->date('to_date')->nullable();
            $table->boolean('is_others')->default(false);
            $table->string('schedule_for');
            $table->json('days')->nullable();
            $table->boolean('no_lunch_gap')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
