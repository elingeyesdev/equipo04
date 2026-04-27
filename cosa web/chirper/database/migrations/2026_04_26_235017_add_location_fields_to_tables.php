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
            $table->string('provincia')->nullable()->after('longitud');
            $table->string('municipio')->nullable()->after('provincia');
        });

        Schema::table('flood_reports', function (Blueprint $table) {
            $table->string('provincia')->nullable()->after('longitude');
            $table->string('municipio')->nullable()->after('provincia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centros_asistencia', function (Blueprint $table) {
            $table->dropColumn(['provincia', 'municipio']);
        });

        Schema::table('flood_reports', function (Blueprint $table) {
            $table->dropColumn(['provincia', 'municipio']);
        });
    }
};
