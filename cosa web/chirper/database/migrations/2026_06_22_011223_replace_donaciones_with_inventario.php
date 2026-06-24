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
        // 1. Drop existing donaciones table
        Schema::dropIfExists('donaciones');

        // 2. Create inventario table
        Schema::create('inventario', function (Blueprint $table) {
            $table->id();
            
            // Relación con centro_asistencia
            $table->unsignedBigInteger('centro_id');
            $table->foreign('centro_id')
                  ->references('id_centro')
                  ->on('centros_asistencia')
                  ->onDelete('restrict');
            
            $table->string('donor_carnet')->nullable();
            $table->foreign('donor_carnet')
                  ->references('carnet')
                  ->on('users')
                  ->onDelete('set null');

            $table->string('categoria'); // comida, bebida, dinero, ropa, etc.
            $table->text('descripcion');
            $table->boolean('is_anonymous')->default(false);
            $table->string('status')->default('recibido'); // recibido, en_inventario, entregado
            $table->text('usage_details')->nullable();
            
            // Relaciones opcionales (de las anteriores modificaciones de donaciones)
            $table->unsignedBigInteger('inundacion_id')->nullable();
            $table->foreign('inundacion_id')->references('id')->on('inundaciones')->onDelete('set null');

            $table->unsignedBigInteger('victima_id')->nullable();
            $table->foreign('victima_id')->references('id')->on('victimas')->onDelete('set null');

            $table->string('photo_path')->nullable();

            $table->timestamps();
        });

        // 3. Create trazabilidad_inventario for transparency
        Schema::create('trazabilidad_inventario', function (Blueprint $table) {
            $table->id('trazabilidadid');
            $table->unsignedBigInteger('inventario_id');
            $table->string('estado_anterior', 50)->nullable();
            $table->string('estado_nuevo', 50);
            $table->string('ubicacion_actual', 150)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamp('fecha_actualizacion')->useCurrent();
            
            $table->foreign('inventario_id')->references('id')->on('inventario')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trazabilidad_inventario');
        Schema::dropIfExists('inventario');
        
        // Recreation of basic donaciones table (as a rollback fallback)
        Schema::create('donaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('centro_id');
            $table->string('donor_carnet')->nullable();
            $table->text('items_description');
            $table->boolean('is_anonymous')->default(false);
            $table->string('status')->default('recibido');
            $table->text('usage_details')->nullable();
            $table->timestamps();
            // omitting other relations just to have a basic rollback
        });
    }
};
