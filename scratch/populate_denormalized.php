<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$start = microtime(true);

echo "Starting bulk denormalization calculation...\n";

// 1. Reset all clients to default adimplente
DB::statement("
    UPDATE omie_clientes 
    SET divida_comum_total = 0,
        divida_redecard_total = 0,
        qtd_titulos_comum = 0,
        qtd_titulos_redecard = 0,
        dias_atraso_maximo = 0,
        status_cobranca = 'adimplente'
");

// 2. Perform high-performance bulk update from overdue receivables
DB::statement("
    WITH redecard_accounts AS (
        SELECT n_cod_c_c, ulo_source
        FROM omie_contas_correntes
        WHERE codigo_banco = '971' OR descricao ILIKE '%Redecard%'
    ),
    overdue_calc AS (
        SELECT 
            c.cnpj_cpf,
            SUM(CASE WHEN rc.n_cod_c_c IS NULL THEN cp.valor_documento ELSE 0 END) as divida_comum,
            SUM(CASE WHEN rc.n_cod_c_c IS NOT NULL THEN cp.valor_documento ELSE 0 END) as divida_redecard,
            COUNT(CASE WHEN rc.n_cod_c_c IS NULL THEN 1 END) as qtd_comum,
            COUNT(CASE WHEN rc.n_cod_c_c IS NOT NULL THEN 1 END) as qtd_redecard,
            MAX(CURRENT_DATE - cp.data_previsao) as dias_atraso
        FROM omie_contas_receber cp
        JOIN omie_clientes c 
            ON cp.codigo_cliente_fornecedor = c.codigo_cliente_omie 
            AND cp.ulo_source = c.ulo_source
        LEFT JOIN redecard_accounts rc 
            ON cp.id_conta_corrente = rc.n_cod_c_c 
            AND cp.ulo_source = rc.ulo_source
        WHERE cp.status_titulo = 'ATRASADO'
        GROUP BY c.cnpj_cpf
    )
    UPDATE omie_clientes c
    SET 
        divida_comum_total = oc.divida_comum,
        divida_redecard_total = oc.divida_redecard,
        qtd_titulos_comum = oc.qtd_comum,
        qtd_titulos_redecard = oc.qtd_redecard,
        dias_atraso_maximo = GREATEST(oc.dias_atraso, 0),
        status_cobranca = CASE 
            WHEN oc.divida_comum > 0 THEN 'inadimplente'
            WHEN oc.divida_redecard > 0 THEN 'inadimplente_redecard'
            ELSE 'adimplente'
        END
    FROM overdue_calc oc
    WHERE c.cnpj_cpf = oc.cnpj_cpf
");

$time = round((microtime(true) - $start) * 1000, 2);
echo "Bulk Denormalization Completed in {$time}ms!\n";

$counts = DB::select("SELECT status_cobranca, count(*) FROM omie_clientes GROUP BY status_cobranca");
print_r($counts);
