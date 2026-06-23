<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometrics', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('status')->nullable();
            $table->string('ip_address')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('product_name')->nullable();
            $table->unsignedInteger('user_count')->nullable();
            $table->unsignedInteger('admin_count')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('password')->nullable();
            $table->unsignedInteger('log_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometrics');
    }
};
