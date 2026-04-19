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
        Schema::table('centros_asistencia', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'estado', 'contacto_emergencia', 'insumos_necesarios']);
            
            // Usamos TEXT/time con un default para no corromper la DB local existente
            $table->time('hora_apertura')->default('08:00');
            $table->time('hora_cierre')->default('18:00');
            $table->string('contacto')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centros_asistencia', function (Blueprint $table) {
            $table->dropColumn(['hora_apertura', 'hora_cierre', 'contacto']);
            
            $table->enum('tipo', ['Acopio', 'Donación', 'Mixto'])->default('Acopio');
            $table->enum('estado', ['Abierto', 'Lleno', 'Cerrado'])->default('Abierto');
            $table->string('contacto_emergencia')->nullable();
            $table->text('insumos_necesarios')->nullable();
        });
    }
};
