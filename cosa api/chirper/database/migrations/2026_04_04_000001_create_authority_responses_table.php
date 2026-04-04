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
        Schema::create('authority_responses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('flood_report_id')
                ->constrained('flood_reports')
                ->cascadeOnDelete();

            $table->string('authority_carnet', 20);
            $table->foreign('authority_carnet')
                ->references('carnet')
                ->on('users')
                ->cascadeOnDelete();

            $table->text('message');
            $table->timestamps();

            $table->index(['flood_report_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authority_responses');
    }
};
