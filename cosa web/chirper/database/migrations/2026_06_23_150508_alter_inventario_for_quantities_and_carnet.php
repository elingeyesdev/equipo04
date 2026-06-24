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
            // Drop the strict foreign key
            $table->dropForeign(['donor_carnet']);
            
            // Add quantities
            $table->integer('cantidad')->nullable();
            $table->string('unidad_medida', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventario', function (Blueprint $table) {
            $table->dropColumn('cantidad');
            $table->dropColumn('unidad_medida');
            
            // Re-add foreign key
            $table->foreign('donor_carnet')
                  ->references('carnet')
                  ->on('users')
                  ->onDelete('set null');
        });
    }
};
