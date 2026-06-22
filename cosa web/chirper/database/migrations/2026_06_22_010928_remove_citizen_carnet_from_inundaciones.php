<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inundaciones', function (Blueprint $table) {
            $table->dropForeign(['citizen_carnet']);
            $table->dropColumn('citizen_carnet');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inundaciones', function (Blueprint $table) {
            $table->string('citizen_carnet', 20)->nullable();
            $table->foreign('citizen_carnet')->references('carnet')->on('users')->nullOnDelete();
        });
    }
};

