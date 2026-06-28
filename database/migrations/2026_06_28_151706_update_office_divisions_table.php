<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_divisions', function (Blueprint $table) {
            if (!Schema::hasColumn('office_divisions', 'code')) {
                $table->string('code')->after('id');
            }
            if (!Schema::hasColumn('office_divisions', 'description')) {
                $table->string('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('office_divisions', 'office_id')) {
                $table->unsignedBigInteger('office_id')->after('description');
                $table->foreign('office_id')->references('id')->on('offices')->onDelete('cascade');
            }
            if (!Schema::hasColumn('office_divisions', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('office_divisions', function (Blueprint $table) {
            $table->dropForeign(['office_id']);
            $table->dropColumn(['code', 'description', 'office_id']);
            $table->dropSoftDeletes();
        });
    }
};
