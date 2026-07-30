<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OmieCliente;

class ClientesController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get all active ULOs from env to render filters
        $availableUlos = [];
        for ($i = 1; $i <= 5; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            $name = env("ULO_{$num}_NAME");
            if ($name) {
                $availableUlos[] = $name;
            }
        }

        // 2. Manage Session Persistence for Filters
        $tab = $request->input('tab', session('clientes_tab', 'inadimplentes'));
        session(['clientes_tab' => $tab]);

        $search = $request->input('search', session('clientes_search', ''));
        session(['clientes_search' => $search]);

        $sortBy = $request->input('sort_by', session('clientes_sort_by', 'name'));
        session(['clientes_sort_by' => $sortBy]);

        $sortDir = $request->input('sort_dir', session('clientes_sort_dir', 'asc'));
        session(['clientes_sort_dir' => $sortDir]);

        $selectedUlos = $request->input('ulos', session('clientes_ulos', $availableUlos));
        session(['clientes_ulos' => $selectedUlos]);

        $faixa = $request->input('faixa', session('clientes_faixa', 'all'));
        session(['clientes_faixa' => $faixa]);

        // If no ULOs are selected, default to all available to avoid empty query unless user explicitly deselected all
        if (empty($selectedUlos) && !$request->has('ulos')) {
            $selectedUlos = $availableUlos;
        }

        // 3. Build Raw Query
        // We use CTEs (Common Table Expressions) for maximum performance
        $queryStr = "
            WITH all_client_ulos AS (
                SELECT cnpj_cpf, string_agg(DISTINCT ulo_source, ',') as ulos_list
                FROM omie_clientes
                GROUP BY cnpj_cpf
            ),
            redecard_accounts AS (
                SELECT n_cod_c_c, ulo_source
                FROM omie_contas_correntes
                WHERE codigo_banco = '971' OR descricao ILIKE '%Redecard%'
            )
            SELECT 
                c.cnpj_cpf,
                MAX(c.razao_social) as name,
                MAX(c.telefone1_numero) as phone,
                MAX(c.telefone1_ddd) as phone_ddd,
                acu.ulos_list as all_ulos,
                
                -- Dívida normal vencida (não Redecard)
                COALESCE(SUM(CASE WHEN cp.status_titulo = 'ATRASADO' AND rc.n_cod_c_c IS NULL THEN cp.valor_documento ELSE 0 END), 0) as divida_comum,
                
                -- Dívida Redecard vencida
                COALESCE(SUM(CASE WHEN cp.status_titulo = 'ATRASADO' AND rc.n_cod_c_c IS NOT NULL THEN cp.valor_documento ELSE 0 END), 0) as divida_redecard,
                
                -- Quantidade de títulos
                COALESCE(COUNT(CASE WHEN cp.status_titulo = 'ATRASADO' AND rc.n_cod_c_c IS NULL THEN cp.id END), 0) as qtd_titulos_comum,
                COALESCE(COUNT(CASE WHEN cp.status_titulo = 'ATRASADO' AND rc.n_cod_c_c IS NOT NULL THEN cp.id END), 0) as qtd_titulos_redecard,

                -- Dias de atraso (com base no vencido mais antigo)
                COALESCE(CURRENT_DATE - MIN(CASE WHEN cp.status_titulo = 'ATRASADO' THEN cp.data_previsao END), 0) as dias_atraso
            FROM omie_clientes c
            JOIN all_client_ulos acu ON c.cnpj_cpf = acu.cnpj_cpf
            LEFT JOIN omie_contas_receber cp 
                ON c.codigo_cliente_omie = cp.codigo_cliente_fornecedor 
                AND c.ulo_source = cp.ulo_source
            LEFT JOIN redecard_accounts rc 
                ON cp.id_conta_corrente = rc.n_cod_c_c 
                AND cp.ulo_source = rc.ulo_source
            WHERE 1=1
        ";

        $bindings = [];

        // Apply ULO Filter
        if (!empty($selectedUlos)) {
            $placeholders = [];
            foreach ($selectedUlos as $index => $uloName) {
                $bindKey = "ulo_" . $index;
                $placeholders[] = ":" . $bindKey;
                $bindings[$bindKey] = $uloName;
            }
            $queryStr .= " AND c.ulo_source IN (" . implode(',', $placeholders) . ")";
        } else {
            // If empty, force no results
            $queryStr .= " AND 1=0";
        }

        // Apply Search Filter
        if (!empty($search)) {
            $queryStr .= " AND (c.razao_social ILIKE :search OR c.nome_fantasia ILIKE :search OR c.cnpj_cpf ILIKE :search)";
            $bindings['search'] = '%' . $search . '%';
        }

        $queryStr .= " GROUP BY c.cnpj_cpf, acu.ulos_list";

        // Apply Tab Filter & Faixa Filter (HAVING clause)
        $havingClauses = [];
        if ($tab === 'inadimplentes') {
            $havingClauses[] = "COALESCE(SUM(CASE WHEN cp.status_titulo = 'ATRASADO' AND rc.n_cod_c_c IS NULL THEN cp.valor_documento ELSE 0 END), 0) > 0";
        } elseif ($tab === 'inadimplentes_redecard') {
            $havingClauses[] = "COALESCE(SUM(CASE WHEN cp.status_titulo = 'ATRASADO' AND rc.n_cod_c_c IS NULL THEN cp.valor_documento ELSE 0 END), 0) = 0";
            $havingClauses[] = "COALESCE(SUM(CASE WHEN cp.status_titulo = 'ATRASADO' AND rc.n_cod_c_c IS NOT NULL THEN cp.valor_documento ELSE 0 END), 0) > 0";
        } else { // adimplentes
            $havingClauses[] = "COALESCE(SUM(CASE WHEN cp.status_titulo = 'ATRASADO' THEN cp.valor_documento ELSE 0 END), 0) = 0";
        }

        // Apply Aging Faixa Filter (only makes sense for inadimplentes tabs)
        if ($tab !== 'adimplentes' && $faixa !== 'all') {
            if ($faixa === '30') {
                $havingClauses[] = "COALESCE(CURRENT_DATE - MIN(CASE WHEN cp.status_titulo = 'ATRASADO' THEN cp.data_previsao END), 0) <= 30";
            } elseif ($faixa === '90') {
                $havingClauses[] = "COALESCE(CURRENT_DATE - MIN(CASE WHEN cp.status_titulo = 'ATRASADO' THEN cp.data_previsao END), 0) >= 31 AND COALESCE(CURRENT_DATE - MIN(CASE WHEN cp.status_titulo = 'ATRASADO' THEN cp.data_previsao END), 0) <= 90";
            } elseif ($faixa === '120') {
                $havingClauses[] = "COALESCE(CURRENT_DATE - MIN(CASE WHEN cp.status_titulo = 'ATRASADO' THEN cp.data_previsao END), 0) >= 91";
            }
        }

        if (count($havingClauses) > 0) {
            $queryStr .= " HAVING " . implode(' AND ', $havingClauses);
        }

        // Apply Order By
        $allowedSorts = [
            'name' => 'name',
            'cnpj' => 'c.cnpj_cpf',
            'divida' => ($tab === 'inadimplentes_redecard' ? 'divida_redecard' : 'divida_comum'),
            'atraso' => 'dias_atraso'
        ];
        $sortColumn = $allowedSorts[$sortBy] ?? 'name';
        $queryStr .= " ORDER BY {$sortColumn} " . (strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC');

        // 4. Manual Paginate the raw query results for accuracy and speed
        $results = DB::select($queryStr, $bindings);
        $total = count($results);

        // Calculate totals for stats panel
        $totalOverdueTitlesCount = 0;
        $totalOverdueAmount = 0.0;
        foreach ($results as $row) {
            if ($tab === 'inadimplentes_redecard') {
                $totalOverdueTitlesCount += (int)$row->qtd_titulos_redecard;
                $totalOverdueAmount += (float)$row->divida_redecard;
            } else if ($tab === 'inadimplentes') {
                $totalOverdueTitlesCount += (int)$row->qtd_titulos_comum;
                $totalOverdueAmount += (float)$row->divida_comum;
            }
        }

        $perPage = 15;
        $page = $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $pagedData = array_slice($results, $offset, $perPage);

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $pagedData,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('pages.clientes.index', compact(
            'paginator',
            'availableUlos',
            'selectedUlos',
            'tab',
            'search',
            'sortBy',
            'sortDir',
            'faixa',
            'totalOverdueTitlesCount',
            'totalOverdueAmount'
        ));
    }
}
