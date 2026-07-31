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
        Schema::table('omie_clientes', function (Blueprint $table) {
            $table->index('cnpj_cpf', 'idx_omie_clientes_cnpj_cpf');
            $table->index(['ulo_source', 'cnpj_cpf'], 'idx_omie_clientes_ulo_cnpj');
        });

        Schema::table('omie_contas_receber', function (Blueprint $table) {
            $table->index('status_titulo', 'idx_omie_cr_status');
            $table->index('id_conta_corrente', 'idx_omie_cr_cc');
            $table->index(['ulo_source', 'codigo_cliente_fornecedor', 'status_titulo'], 'idx_omie_cr_ulo_cli_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('omie_clientes', function (Blueprint $table) {
            $table->dropIndex('idx_omie_clientes_cnpj_cpf');
            $table->dropIndex('idx_omie_clientes_ulo_cnpj');
        });

        Schema::table('omie_contas_receber', function (Blueprint $table) {
            $table->dropIndex('idx_omie_cr_status');
            $table->dropIndex('idx_omie_cr_cc');
            $table->dropIndex('idx_omie_cr_ulo_cli_status');
        });
    }
};
