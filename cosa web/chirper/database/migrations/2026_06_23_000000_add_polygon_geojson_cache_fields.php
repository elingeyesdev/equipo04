<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->jsonb('polygon_geojson')->nullable()->after('polygon_coords');
            $table->timestamp('polygon_calculado_at')->nullable()->after('polygon_geojson');
            $table->boolean('polygon_es_fallback')->default(false)->after('polygon_calculado_at');
        });

        Schema::table('inundaciones', function (Blueprint $table) {
            $table->jsonb('polygon_geojson')->nullable()->after('polygon_coords');
            $table->boolean('polygon_es_fallback')->default(false)->after('polygon_calculado_at');
        });
    }

    public function down(): void
    {
        Schema::table('reportes', function (Blueprint $table) {
            $table->dropColumn(['polygon_geojson', 'polygon_calculado_at', 'polygon_es_fallback']);
        });

        Schema::table('inundaciones', function (Blueprint $table) {
            $table->dropColumn(['polygon_geojson', 'polygon_es_fallback']);
        });
    }
};
