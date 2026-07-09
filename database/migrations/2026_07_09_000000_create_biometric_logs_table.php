<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biometric_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('biometric_id')->nullable()->constrained('biometrics')->nullOnDelete();
            $table->string('device_name')->nullable();
            $table->string('action');
            $table->string('status');
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->string('performed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_logs');
    }
};
