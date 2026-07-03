<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkinout', function (Blueprint $table) {
            $table->id();
            $table->string('badge_number');
            $table->dateTime('check_time');
            $table->string('serial_number');
            $table->dateTime('date_posted')->nullable();
            $table->string('posted_by')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkinout');
    }
};
