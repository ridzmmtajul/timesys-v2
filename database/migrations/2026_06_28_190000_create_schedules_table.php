<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->time('default_timein_AM')->nullable();
            $table->time('default_timeout_AM')->nullable();
            $table->time('default_timein_PM')->nullable();
            $table->time('default_timeout_PM')->nullable();
            $table->foreignId('schedule_type_id')->nullable()->constrained('schedule_types')->cascadeOnDelete();
            $table->boolean('no_lunch_gap')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
