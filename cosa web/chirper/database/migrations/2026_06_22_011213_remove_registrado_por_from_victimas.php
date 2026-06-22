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
        Schema::table('victimas', function (Blueprint $table) {
            $table->dropForeign(['registrado_por']);
            $table->dropColumn('registrado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('victimas', function (Blueprint $table) {
            $table->string('registrado_por', 20)->nullable();
            $table->foreign('registrado_por')->references('carnet')->on('users')->nullOnDelete();
        });
    }
};

