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
        Schema::create('centros_asistencia', function (Blueprint $table) {
            $table->id('id_centro');
            $table->string('nombre');
            $table->enum('tipo', ['Acopio', 'Donación', 'Mixto']);
            $table->string('direccion')->nullable();
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->enum('estado', ['Abierto', 'Lleno', 'Cerrado'])->default('Abierto');
            $table->string('contacto_emergencia')->nullable();
            $table->string('encargado')->nullable();
            $table->text('insumos_necesarios')->nullable();
            
            // Usamos manual update de timestamp en vez del genérico timestamps
            $table->timestamp('ultima_actualizacion')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centros_asistencia');
    }
};
