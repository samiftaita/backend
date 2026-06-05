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
        Schema::create('fiche_soins', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dossier_medical_id')
                ->constrained('dossier_medicals')
                ->onDelete('cascade');

            $table->foreignId('dentiste_id')
                ->constrained('dentistes')
                ->onDelete('cascade');

            $table->date('date_soin');
            $table->text('description');
            $table->text('observation')->nullable();
            $table->decimal('prix', 8, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiche_soins');
    }
};
