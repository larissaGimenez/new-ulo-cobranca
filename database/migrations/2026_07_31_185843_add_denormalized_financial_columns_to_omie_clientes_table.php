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
            $table->decimal('divida_comum_total', 12, 2)->default(0.00)->after('email');
            $table->decimal('divida_redecard_total', 12, 2)->default(0.00)->after('divida_comum_total');
            $table->integer('qtd_titulos_comum')->default(0)->after('divida_redecard_total');
            $table->integer('qtd_titulos_redecard')->default(0)->after('qtd_titulos_comum');
            $table->integer('dias_atraso_maximo')->default(0)->after('qtd_titulos_redecard');
            $table->string('status_cobranca', 30)->default('adimplente')->after('dias_atraso_maximo');

            $table->index('status_cobranca', 'idx_omie_clientes_status_cobranca');
            $table->index(['status_cobranca', 'dias_atraso_maximo'], 'idx_omie_clientes_status_dias');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('omie_clientes', function (Blueprint $table) {
            $table->dropIndex('idx_omie_clientes_status_cobranca');
            $table->dropIndex('idx_omie_clientes_status_dias');

            $table->dropColumn([
                'divida_comum_total',
                'divida_redecard_total',
                'qtd_titulos_comum',
                'qtd_titulos_redecard',
                'dias_atraso_maximo',
                'status_cobranca',
            ]);
        });
    }
};
