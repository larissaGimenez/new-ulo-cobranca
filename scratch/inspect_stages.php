<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CLIENT KANBAN STAGES ===\n";
print_r(DB::table('client_kanban_stages')->get()->toArray());

echo "\n=== KANBAN COLUMNS ===\n";
print_r(DB::table('kanban_columns')->get()->toArray());

echo "\n=== SARAH IN OMIE CLIENTES ===\n";
print_r(DB::table('omie_clientes')->where('razao_social', 'ILIKE', '%Sarah%')->get(['codigo_cliente_omie', 'razao_social', 'cnpj_cpf', 'ulo_source'])->toArray());
