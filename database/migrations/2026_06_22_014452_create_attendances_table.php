<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->dateTime('check_time');
            $table->char('employee_id', 36);
            $table->string('serial_no')->nullable();
            $table->unsignedBigInteger('post_no')->nullable();
            $table->boolean('void')->default(false);
            $table->timestamps();

            $table->unique(['check_time', 'employee_id', 'post_no'], 'unique_attendance');

            $table->index('employee_id', 'attendances_employee_id_foreign');
            $table->index('post_no', 'attendances_post_no_foreign');
            $table->index('check_time', 'attendances_check_time_foreign');
            $table->index('serial_no', 'attendances_serial_no_foreign');
            $table->index(['check_time', 'employee_id', 'post_no'], 'attendances_dtr_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
