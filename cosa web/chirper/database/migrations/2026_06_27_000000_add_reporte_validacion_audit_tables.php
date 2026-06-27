<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motivos_rechazo', function (Blueprint $table) {
            $table->string('codigo', 50)->primary();
            $table->text('label_autoridad');
            $table->text('label_ciudadano');
            $table->boolean('requiere_nota')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('reportes', function (Blueprint $table) {
            $table->string('validador_id', 20)->nullable()->after('estado_validacion');
            $table->string('motivo_rechazo_codigo', 50)->nullable()->after('validador_id');
            $table->text('motivo_rechazo_texto')->nullable()->after('motivo_rechazo_codigo');
            $table->timestampTz('rechazado_at')->nullable()->after('motivo_rechazo_texto');
            $table->timestampTz('validado_at')->nullable()->after('rechazado_at');
            $table->decimal('distancia_gps_metros', 8, 2)->nullable()->after('validado_at');
            $table->string('intensidad_validada', 10)->nullable()->after('intensidad_propuesta');
            $table->text('ajuste_comentario')->nullable()->after('intensidad_validada');

            $table->foreign('validador_id')
                ->references('carnet')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('motivo_rechazo_codigo')
                ->references('codigo')
                ->on('motivos_rechazo')
                ->restrictOnDelete();

            $table->index('estado_validacion');
            $table->index(['citizen_carnet', 'estado_validacion']);
        });

        Schema::create('reporte_validacion_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_id')->constrained('reportes')->cascadeOnDelete();
            $table->string('estado_anterior', 20);
            $table->string('estado_nuevo', 20);
            $table->string('accion', 40);
            $table->string('validador_id', 20)->nullable();
            $table->string('motivo_codigo', 50)->nullable();
            $table->text('motivo_texto')->nullable();
            $table->unsignedBigInteger('inundacion_id_anterior')->nullable();
            $table->unsignedBigInteger('inundacion_id_nuevo')->nullable();
            $table->string('intensidad_propuesta_snapshot', 10)->nullable();
            $table->string('intensidad_validada_snapshot', 10)->nullable();
            $table->jsonb('metadata_json')->nullable();
            $table->timestampTz('fecha_accion')->useCurrent();

            $table->foreign('validador_id')
                ->references('carnet')
                ->on('users')
                ->nullOnDelete();

            $table->foreign('motivo_codigo')
                ->references('codigo')
                ->on('motivos_rechazo')
                ->restrictOnDelete();

            $table->foreign('inundacion_id_anterior')
                ->references('id')
                ->on('inundaciones')
                ->nullOnDelete();

            $table->foreign('inundacion_id_nuevo')
                ->references('id')
                ->on('inundaciones')
                ->nullOnDelete();

            $table->index(['reporte_id', 'fecha_accion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reporte_validacion_historial');

        Schema::table('reportes', function (Blueprint $table) {
            $table->dropForeign(['validador_id']);
            $table->dropForeign(['motivo_rechazo_codigo']);
            $table->dropIndex(['estado_validacion']);
            $table->dropIndex(['citizen_carnet', 'estado_validacion']);
            $table->dropColumn([
                'validador_id',
                'motivo_rechazo_codigo',
                'motivo_rechazo_texto',
                'rechazado_at',
                'validado_at',
                'distancia_gps_metros',
                'intensidad_validada',
                'ajuste_comentario',
            ]);
        });

        Schema::dropIfExists('motivos_rechazo');
    }
};
