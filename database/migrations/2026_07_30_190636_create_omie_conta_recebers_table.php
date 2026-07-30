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
        Schema::create('omie_contas_receber', function (Blueprint $table) {
            $table->id();
            $table->string('ulo_source');
            $table->bigInteger('codigo_lancamento_omie');
            $table->bigInteger('codigo_cliente_fornecedor');
            $table->string('codigo_categoria')->nullable();
            $table->string('codigo_tipo_documento')->nullable();
            
            $table->date('data_emissao')->nullable();
            $table->date('data_vencimento')->nullable();
            $table->date('data_previsao')->nullable();
            $table->date('data_registro')->nullable();
            
            $table->bigInteger('id_conta_corrente')->nullable();
            $table->string('id_origem')->nullable();
            $table->string('numero_parcela')->nullable();
            $table->string('status_titulo')->nullable();
            $table->decimal('valor_documento', 15, 2)->nullable();
            
            $table->char('bloqueado', 1)->nullable();
            $table->char('bloquear_baixa', 1)->nullable();

            // JSON / JSONB columns
            $table->jsonb('boleto')->nullable();
            $table->jsonb('categorias')->nullable();
            $table->jsonb('distribuicao')->nullable();
            $table->jsonb('info')->nullable();

            $table->timestamps();

            // Unique index to prevent duplicate accounts receivable entries from the same source
            $table->unique(['ulo_source', 'codigo_lancamento_omie']);
            
            // Relationship index
            $table->index(['ulo_source', 'codigo_cliente_fornecedor'], 'idx_ulo_cliente_receber');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('omie_contas_receber');
    }
};
