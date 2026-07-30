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
        Schema::create('omie_contas_pagar', function (Blueprint $table) {
            $table->id();
            $table->string('ulo_source');
            $table->bigInteger('codigo_lancamento_omie');
            $table->bigInteger('codigo_cliente_fornecedor')->nullable();
            $table->string('codigo_categoria')->nullable();
            $table->string('codigo_tipo_documento')->nullable();
            $table->date('data_emissao')->nullable();
            $table->date('data_entrada')->nullable();
            $table->date('data_previsao')->nullable();
            $table->date('data_vencimento')->nullable();
            $table->bigInteger('id_conta_corrente')->nullable();
            $table->string('id_origem')->nullable();
            $table->string('numero_documento')->nullable();
            $table->string('numero_documento_fiscal')->nullable();
            $table->string('numero_parcela')->nullable();
            $table->string('status_titulo')->nullable();
            $table->decimal('valor_documento', 15, 2)->nullable();
            $table->string('chave_nfe')->nullable();
            $table->string('operacao')->nullable();
            $table->char('baixa_bloqueada', 1)->nullable();
            $table->char('bloqueado', 1)->nullable();
            $table->text('codigo_barras_ficha_compensacao')->nullable();
            
            // Retenções
            $table->char('retem_cofins', 1)->nullable();
            $table->char('retem_csll', 1)->nullable();
            $table->char('retem_inss', 1)->nullable();
            $table->char('retem_ir', 1)->nullable();
            $table->char('retem_iss', 1)->nullable();
            $table->char('retem_pis', 1)->nullable();

            // JSON / JSONB fields for sub-arrays/objects
            $table->jsonb('info')->nullable();
            $table->jsonb('categorias')->nullable();
            $table->jsonb('cnab_integracao_bancaria')->nullable();
            $table->jsonb('distribuicao')->nullable();

            $table->timestamps();

            // Unique index to prevent duplicate accounts payable entries from the same source
            $table->unique(['ulo_source', 'codigo_lancamento_omie']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('omie_contas_pagar');
    }
};
