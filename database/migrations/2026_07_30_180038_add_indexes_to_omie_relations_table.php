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
        Schema::table('omie_contas_pagar', function (Blueprint $table) {
            $table->index(['ulo_source', 'codigo_cliente_fornecedor'], 'idx_ulo_cliente_fornecedor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('omie_contas_pagar', function (Blueprint $table) {
            $table->dropIndex('idx_ulo_cliente_fornecedor');
        });
    }
};
