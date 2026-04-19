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
        Schema::create('flood_reports', function (Blueprint $table) {
            $table->id();

            $table->string('citizen_carnet', 20);
            $table->foreign('citizen_carnet')
                ->references('carnet')
                ->on('users')
                ->cascadeOnDelete();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('address')->nullable();
            $table->text('description');

            $table->string('severity')->default('medium');
            $table->string('status')->default('open');

            $table->timestamps();

            $table->index(['citizen_carnet', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flood_reports');
    }
};
