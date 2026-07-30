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
        Schema::create('omie_contas_correntes', function (Blueprint $table) {
            $table->id();
            $table->string('ulo_source');
            $table->bigInteger('n_cod_c_c');
            $table->string('descricao');
            $table->string('codigo_banco')->nullable();
            $table->string('tipo')->nullable();
            $table->string('codigo_agencia')->nullable();
            $table->string('conta_corrente')->nullable();
            $table->string('c_cod_c_c_int')->nullable();
            $table->string('c_sincr_analitica')->nullable();
            $table->timestamps();

            // Unique index to prevent duplicate entries from the same source
            $table->unique(['ulo_source', 'n_cod_c_c']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('omie_contas_correntes');
    }
};
