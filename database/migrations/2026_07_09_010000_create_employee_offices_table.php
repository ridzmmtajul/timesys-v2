<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_offices', function (Blueprint $table) {
            $table->id();
            $table->char('employee_id', 36);
            $table->foreignId('office_id')->constrained('offices')->cascadeOnDelete();
            $table->string('employee_no');
            $table->string('synced_from')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();

            // employee_no is assigned independently by each biometric device
            // (identified by synced_from) - it's only unique within that
            // device's own enrollment list, not within an office (an office
            // can have more than one device, and numbering isn't coordinated
            // between devices).
            $table->unique(['synced_from', 'employee_no']);
            // An employee can only be enrolled once per device.
            $table->unique(['employee_id', 'synced_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_offices');
    }
};
