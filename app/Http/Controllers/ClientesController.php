<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OmieCliente;
use Illuminate\Support\Str;

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

        // 2. Manage Session Persistence for Filters & View Mode
        $viewMode = $request->input('view_mode', session('clientes_view_mode', 'lista'));
        session(['clientes_view_mode' => $viewMode]);

        $tab = $request->input('tab', session('clientes_tab', 'inadimplentes'));
        session(['clientes_tab' => $tab]);

        $sortBy = $request->input('sort_by', session('clientes_sort_by', 'name'));
        session(['clientes_sort_by' => $sortBy]);

        $sortDir = $request->input('sort_dir', session('clientes_sort_dir', 'asc'));
        session(['clientes_sort_dir' => $sortDir]);

        if ($request->has('clear')) {
            session()->forget(['clientes_search', 'clientes_ulos', 'clientes_faixa']);
            $search = '';
            $selectedUlos = $availableUlos;
            $faixa = 'all';
        } else {
            if ($request->has('search')) {
                $search = $request->input('search') ?? '';
            } else {
                $search = session('clientes_search', '');
            }

            if ($request->has('ulos')) {
                $selectedUlos = $request->input('ulos', []);
            } else {
                $selectedUlos = session('clientes_ulos', $availableUlos);
            }

            if ($request->has('faixa')) {
                $faixa = $request->input('faixa') ?? 'all';
            } else {
                $faixa = session('clientes_faixa', 'all');
            }
        }

        session(['clientes_search' => $search]);
        session(['clientes_ulos' => $selectedUlos]);
        session(['clientes_faixa' => $faixa]);

        // If no ULOs are selected, default to all available to avoid empty query unless user explicitly deselected all
        if (empty($selectedUlos) && !$request->has('ulos')) {
            $selectedUlos = $availableUlos;
        }

        // 3. Build Raw Query with CTEs and Kanban Stage Left Join
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
                
                -- Kanban Stage
                COALESCE(
                    MAX(ks.stage), 
                    CASE WHEN SUM(CASE WHEN cp.status_titulo = 'ATRASADO' THEN cp.valor_documento ELSE 0 END) > 0 
                        THEN 'inadimplencia' 
                        ELSE 'pagamento_concluido' 
                    END
                ) as stage,

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
            LEFT JOIN client_kanban_stages ks ON c.cnpj_cpf = ks.cnpj_cpf
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

        // 4. Fetch raw query results
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

        // Fetch Dynamic Kanban Columns from Database
        $dbColumns = DB::table('kanban_columns')->orderBy('position', 'asc')->get();
        if ($dbColumns->isEmpty()) {
            $dbColumns = collect([
                (object)['slug' => 'inadimplencia', 'title' => 'INADIMPLÊNCIA', 'dot_color' => 'bg-error', 'border_color' => 'border-t-4 border-t-error', 'position' => 1],
                (object)['slug' => 'contato_inicial', 'title' => 'CONTATO INICIAL', 'dot_color' => 'bg-warning', 'border_color' => 'border-t-4 border-t-warning', 'position' => 2],
                (object)['slug' => 'em_negociacao', 'title' => 'EM NEGOCIAÇÃO', 'dot_color' => 'bg-info', 'border_color' => 'border-t-4 border-t-info', 'position' => 3],
                (object)['slug' => 'acordo_ativo', 'title' => 'ACORDO ATIVO', 'dot_color' => 'bg-primary', 'border_color' => 'border-t-4 border-t-primary', 'position' => 4],
                (object)['slug' => 'pagamento_concluido', 'title' => 'PAGAMENTO CONCLUÍDO', 'dot_color' => 'bg-success', 'border_color' => 'border-t-4 border-t-success', 'position' => 5],
            ]);
        }

        $kanbanColumns = [];
        foreach ($dbColumns as $col) {
            $kanbanColumns[$col->slug] = [
                'id' => $col->slug,
                'title' => mb_strtoupper($col->title),
                'dot_color' => $col->dot_color ?? 'bg-primary',
                'border_color' => $col->border_color ?? 'border-t-4 border-t-primary',
                'position' => $col->position,
                'items' => [],
                'count' => 0,
                'total' => 0.0
            ];
        }

        $firstColSlug = array_key_first($kanbanColumns);

        foreach ($results as $row) {
            $stg = $row->stage ?? $firstColSlug;
            if (!isset($kanbanColumns[$stg])) {
                $stg = $firstColSlug;
            }
            $kanbanColumns[$stg]['items'][] = $row;
            $kanbanColumns[$stg]['count']++;
            $amount = ($tab === 'inadimplentes_redecard') ? (float)$row->divida_redecard : (float)$row->divida_comum;
            $kanbanColumns[$stg]['total'] += $amount;
        }

        // Paginator for list view
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

        $viewData = compact(
            'paginator',
            'availableUlos',
            'selectedUlos',
            'tab',
            'search',
            'sortBy',
            'sortDir',
            'faixa',
            'viewMode',
            'kanbanColumns',
            'totalOverdueTitlesCount',
            'totalOverdueAmount'
        );

        if ($request->ajax()) {
            return view('pages.clientes.partials.table-content', $viewData);
        }

        return view('pages.clientes.index', $viewData);
    }

    /**
     * Update Kanban stage for a client (drag & drop)
     */
    public function updateStage(Request $request)
    {
        $cnpjCpf = $request->input('cnpj_cpf');
        $stage = $request->input('stage');

        if (!$cnpjCpf || !$stage) {
            return response()->json(['error' => 'Dados inválidos'], 400);
        }

        DB::table('client_kanban_stages')->updateOrInsert(
            ['cnpj_cpf' => $cnpjCpf],
            [
                'stage' => $stage,
                'updated_at' => now(),
                'created_at' => now()
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Store a new custom Kanban column
     */
    public function storeColumn(Request $request)
    {
        $title = trim($request->input('title'));
        if (empty($title)) {
            return response()->json(['error' => 'Título da coluna é obrigatório'], 400);
        }

        $slug = Str::slug($title, '_');
        if (empty($slug)) {
            $slug = 'col_' . time();
        }

        $existing = DB::table('kanban_columns')->where('slug', $slug)->exists();
        if ($existing) {
            $slug = $slug . '_' . rand(100, 999);
        }

        $maxPos = DB::table('kanban_columns')->max('position') ?? 0;

        $colors = [
            ['dot' => 'bg-purple-500', 'border' => 'border-t-4 border-t-purple-500'],
            ['dot' => 'bg-pink-500', 'border' => 'border-t-4 border-t-pink-500'],
            ['dot' => 'bg-indigo-500', 'border' => 'border-t-4 border-t-indigo-500'],
            ['dot' => 'bg-teal-500', 'border' => 'border-t-4 border-t-teal-500'],
            ['dot' => 'bg-amber-500', 'border' => 'border-t-4 border-t-amber-500'],
        ];
        $colorPick = $colors[array_rand($colors)];

        DB::table('kanban_columns')->insert([
            'slug' => $slug,
            'title' => mb_strtoupper($title),
            'position' => $maxPos + 1,
            'dot_color' => $colorPick['dot'],
            'border_color' => $colorPick['border'],
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Reorder Kanban columns
     */
    public function reorderColumns(Request $request)
    {
        $order = $request->input('order', []); // array of slugs
        foreach ($order as $pos => $slug) {
            DB::table('kanban_columns')->where('slug', $slug)->update(['position' => $pos + 1]);
        }
        return response()->json(['success' => true]);
    }

    /**
     * Delete a Kanban column
     */
    public function deleteColumn(Request $request)
    {
        $slug = $request->input('slug');
        if (!$slug) {
            return response()->json(['error' => 'Coluna não informada'], 400);
        }

        $firstCol = DB::table('kanban_columns')->where('slug', '!=', $slug)->orderBy('position', 'asc')->first();
        $fallbackSlug = $firstCol ? $firstCol->slug : 'inadimplencia';

        DB::table('client_kanban_stages')->where('stage', $slug)->update(['stage' => $fallbackSlug]);
        DB::table('kanban_columns')->where('slug', $slug)->delete();

        return response()->json(['success' => true]);
    }
}
