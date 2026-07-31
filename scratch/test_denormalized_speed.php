<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$start = microtime(true);

$queryStr = "
    SELECT 
        c.cnpj_cpf,
        MAX(c.razao_social) as name,
        MAX(c.email) as email,
        MAX(c.telefone1_numero) as phone,
        MAX(c.telefone1_ddd) as phone_ddd,
        string_agg(DISTINCT c.ulo_source, ',') as all_ulos,
        COALESCE(MAX(ks.stage), CASE WHEN MAX(c.status_cobranca) != 'adimplente' THEN 'inadimplencia' ELSE 'pagamento_concluido' END) as stage,
        MAX(c.divida_comum_total) as divida_comum,
        MAX(c.divida_redecard_total) as divida_redecard,
        MAX(c.qtd_titulos_comum) as qtd_titulos_comum,
        MAX(c.qtd_titulos_redecard) as qtd_titulos_redecard,
        MAX(c.dias_atraso_maximo) as dias_atraso
    FROM omie_clientes c
    LEFT JOIN client_kanban_stages ks ON c.cnpj_cpf = ks.cnpj_cpf
    WHERE c.ulo_source IN ('ULO 01', 'ULO 02', 'ULO 03', 'ULO 04', 'ULO 05')
      AND c.status_cobranca = 'inadimplente'
    GROUP BY c.cnpj_cpf
    ORDER BY name ASC
";

$results = DB::select($queryStr);
$time = round((microtime(true) - $start) * 1000, 2);

echo "Denormalized Direct SELECT Time: {$time}ms (Total Rows: " . count($results) . ")\n";
