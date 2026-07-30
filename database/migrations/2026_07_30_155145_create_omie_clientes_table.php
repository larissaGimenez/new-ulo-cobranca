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
        Schema::create('omie_clientes', function (Blueprint $table) {
            $table->id();
            $table->string('ulo_source');
            $table->bigInteger('codigo_cliente_omie');
            $table->string('codigo_cliente_integracao')->nullable();
            $table->string('cnpj_cpf');
            $table->string('razao_social');
            $table->string('nome_fantasia');
            
            $table->string('bairro')->nullable();
            $table->string('cep')->nullable();
            $table->string('cidade')->nullable();
            $table->string('cidade_ibge')->nullable();
            $table->string('estado')->nullable();
            $table->string('endereco')->nullable();
            $table->string('endereco_numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('email')->nullable();
            $table->string('telefone1_ddd')->nullable();
            $table->string('telefone1_numero')->nullable();
            
            $table->char('inativo', 1)->default('N');
            $table->char('pessoa_fisica', 1)->default('N');

            // JSON / JSONB columns
            $table->jsonb('dadosBancarios')->nullable();
            $table->jsonb('recomendacoes')->nullable();
            $table->jsonb('tags')->nullable();
            $table->jsonb('info')->nullable();

            $table->timestamps();

            // Unique index to prevent duplicate customer entries from the same source
            $table->unique(['ulo_source', 'codigo_cliente_omie']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('omie_clientes');
    }
};
