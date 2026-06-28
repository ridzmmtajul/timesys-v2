<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_time_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule');
            $table->string('description')->nullable();
            $table->string('time');
            $table->longText('offices')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_time_rules');
    }
};
