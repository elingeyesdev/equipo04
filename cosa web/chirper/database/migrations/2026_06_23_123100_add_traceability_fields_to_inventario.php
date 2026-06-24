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
        Schema::table('inventario', function (Blueprint $table) {
            $table->string('registrado_por')->nullable();
            $table->foreign('registrado_por')->references('carnet')->on('users')->onDelete('set null');
        });

        Schema::table('trazabilidad_inventario', function (Blueprint $table) {
            $table->string('registrado_por')->nullable();
            $table->foreign('registrado_por')->references('carnet')->on('users')->onDelete('set null');
            $table->string('photo_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trazabilidad_inventario', function (Blueprint $table) {
            $table->dropForeign(['registrado_por']);
            $table->dropColumn('registrado_por');
            $table->dropColumn('photo_path');
        });

        Schema::table('inventario', function (Blueprint $table) {
            $table->dropForeign(['registrado_por']);
            $table->dropColumn('registrado_por');
        });
    }
};
