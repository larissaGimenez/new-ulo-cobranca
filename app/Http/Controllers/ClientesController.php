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
        $allStageSlugs = $dbColumns->pluck('slug')->toArray();

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
            session()->forget(['clientes_search', 'clientes_ulos', 'clientes_faixa', 'clientes_stages']);
            $search = '';
            $selectedUlos = $availableUlos;
            $faixa = 'all';
            $selectedStages = $allStageSlugs;
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

            if ($request->has('stages')) {
                $selectedStages = $request->input('stages', []);
            } else {
                $selectedStages = session('clientes_stages', $allStageSlugs);
            }
        }

        session(['clientes_search' => $search]);
        session(['clientes_ulos' => $selectedUlos]);
        session(['clientes_faixa' => $faixa]);
        session(['clientes_stages' => $selectedStages]);

        // Fallback if empty
        if (empty($selectedUlos) && !$request->has('ulos')) {
            $selectedUlos = $availableUlos;
        }
        if (empty($selectedStages) && !$request->has('stages')) {
            $selectedStages = $allStageSlugs;
        }

        // 3. Build Ultra-Fast Denormalized Query directly from omie_clientes
        $bindings = [];
        $cleanUlos = !empty($selectedUlos) ? array_map(fn($u) => "'" . addslashes($u) . "'", $selectedUlos) : ["'1=0'"];
        $uloFilterSql = "c.ulo_source IN (" . implode(',', $cleanUlos) . ")";

        $whereClauses = [$uloFilterSql];

        // Filter by Tab (status_cobranca)
        if ($tab === 'inadimplentes') {
            $whereClauses[] = "c.status_cobranca = 'inadimplente'";
        } elseif ($tab === 'inadimplentes_redecard') {
            $whereClauses[] = "c.status_cobranca = 'inadimplente_redecard'";
        } else { // adimplentes
            $whereClauses[] = "c.status_cobranca = 'adimplente'";
        }

        // Apply Aging Faixa Filter
        if ($tab !== 'adimplentes' && $faixa !== 'all') {
            if ($faixa === '30') {
                $whereClauses[] = "c.dias_atraso_maximo <= 30";
            } elseif ($faixa === '90') {
                $whereClauses[] = "c.dias_atraso_maximo >= 31 AND c.dias_atraso_maximo <= 90";
            } elseif ($faixa === '120') {
                $whereClauses[] = "c.dias_atraso_maximo >= 91";
            }
        }

        // Apply Search Filter
        if (!empty($search)) {
            $whereClauses[] = "(c.razao_social ILIKE :search OR c.nome_fantasia ILIKE :search OR c.cnpj_cpf ILIKE :search OR c.email ILIKE :search OR c.telefone1_numero ILIKE :search)";
            $bindings['search'] = '%' . $search . '%';
        }

        // Apply Stage Filter
        if (!empty($selectedStages) && count($selectedStages) < count($allStageSlugs)) {
            $stagePlaceholders = [];
            foreach ($selectedStages as $idx => $stgSlug) {
                $key = "stg_" . $idx;
                $stagePlaceholders[] = ":" . $key;
                $bindings[$key] = $stgSlug;
            }
            $defaultStageExpr = ($tab !== 'adimplentes' ? "'inadimplencia'" : "'pagamento_concluido'");
            $whereClauses[] = "COALESCE(ks.stage, {$defaultStageExpr}) IN (" . implode(',', $stagePlaceholders) . ")";
        }

        $whereSql = implode(" AND ", $whereClauses);

        $defaultStageSql = ($tab !== 'adimplentes' ? "'inadimplencia'" : "'pagamento_concluido'");

        $queryStr = "
            SELECT 
                c.cnpj_cpf,
                MAX(c.razao_social) as name,
                MAX(c.email) as email,
                MAX(c.telefone1_numero) as phone,
                MAX(c.telefone1_ddd) as phone_ddd,
                string_agg(DISTINCT c.ulo_source, ',') as all_ulos,
                COALESCE(MAX(ks.stage), {$defaultStageSql}) as stage,
                MAX(c.divida_comum_total) as divida_comum,
                MAX(c.divida_redecard_total) as divida_redecard,
                MAX(c.qtd_titulos_comum) as qtd_titulos_comum,
                MAX(c.qtd_titulos_redecard) as qtd_titulos_redecard,
                MAX(c.dias_atraso_maximo) as dias_atraso
            FROM omie_clientes c
            LEFT JOIN client_kanban_stages ks ON c.cnpj_cpf = ks.cnpj_cpf
            WHERE {$whereSql}
            GROUP BY c.cnpj_cpf
        ";

        // Apply Order By
        $allowedSorts = [
            'name' => 'name',
            'email' => 'email',
            'stage' => 'stage',
            'divida' => ($tab === 'inadimplentes_redecard' ? 'divida_redecard' : 'divida_comum'),
            'atraso' => 'dias_atraso',
            'faixa' => 'dias_atraso',
            'phone' => 'phone'
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
            $row->stage_title = $kanbanColumns[$stg]['title'];
            $row->stage_dot_color = $kanbanColumns[$stg]['dot_color'];

            $kanbanColumns[$stg]['count']++;
            $amount = ($tab === 'inadimplentes_redecard') ? (float)$row->divida_redecard : (float)$row->divida_comum;
            $kanbanColumns[$stg]['total'] += $amount;
            $kanbanColumns[$stg]['all_items'][] = $row;
        }

        // Limit initial rendered items per column to 20 for instant DOM loading
        foreach ($kanbanColumns as $colId => &$colData) {
            $colData['items'] = array_slice($colData['all_items'] ?? [], 0, 20);
            unset($colData['all_items']);
        }
        unset($colData);

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
            'dbColumns',
            'selectedStages',
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
     * Update Kanban stage for a client with Race Condition & Concurrency Locking protection
     */
    public function updateStage(Request $request)
    {
        $cnpjCpf = $request->input('cnpj_cpf');
        $stage = $request->input('stage');

        if (!$cnpjCpf || !$stage) {
            return response()->json(['error' => 'Dados inválidos'], 400);
        }

        $now = now();
        $updatedRecord = null;

        DB::transaction(function () use ($cnpjCpf, $stage, $now, &$updatedRecord) {
            // Lock row for update to eliminate race condition write collisions
            $existing = DB::table('client_kanban_stages')
                ->where('cnpj_cpf', $cnpjCpf)
                ->lockForUpdate()
                ->first();

            DB::table('client_kanban_stages')->updateOrInsert(
                ['cnpj_cpf' => $cnpjCpf],
                [
                    'stage' => $stage,
                    'updated_at' => $now,
                    'created_at' => $existing ? $existing->created_at : $now
                ]
            );

            $updatedRecord = [
                'cnpj_cpf' => $cnpjCpf,
                'stage' => $stage,
                'updated_at' => $now->toIso8601String()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $updatedRecord
        ]);
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

        // Count how many clients are explicitly in this stage
        $clientCount = DB::table('client_kanban_stages')->where('stage', $slug)->count();
        if ($clientCount > 0) {
            return response()->json(['error' => 'Não é possível excluir uma coluna que possui cards. Mova ou remova os cards primeiro.'], 400);
        }

        DB::table('kanban_columns')->where('slug', $slug)->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Lazy Load next page of cards for a specific Kanban column
     */
    public function loadMoreColumn(Request $request)
    {
        $stageSlug = $request->input('stage');
        $page = (int)$request->input('page', 2);
        $perPage = 20;

        $viewMode = session('clientes_view_mode', 'lista');
        $tab = session('clientes_tab', 'inadimplentes');
        $sortBy = session('clientes_sort_by', 'name');
        $sortDir = session('clientes_sort_dir', 'asc');
        $search = session('clientes_search', '');
        $selectedUlos = session('clientes_ulos', []);
        $faixa = session('clientes_faixa', 'all');

        $cleanUlos = !empty($selectedUlos) ? array_map(fn($u) => "'" . addslashes($u) . "'", $selectedUlos) : ["'1=0'"];
        $uloFilterSql = "c.ulo_source IN (" . implode(',', $cleanUlos) . ")";

        $whereClauses = [$uloFilterSql];
        $bindings = [];

        if ($tab === 'inadimplentes') {
            $whereClauses[] = "c.status_cobranca = 'inadimplente'";
        } elseif ($tab === 'inadimplentes_redecard') {
            $whereClauses[] = "c.status_cobranca = 'inadimplente_redecard'";
        } else {
            $whereClauses[] = "c.status_cobranca = 'adimplente'";
        }

        if ($tab !== 'adimplentes' && $faixa !== 'all') {
            if ($faixa === '30') {
                $whereClauses[] = "c.dias_atraso_maximo <= 30";
            } elseif ($faixa === '90') {
                $whereClauses[] = "c.dias_atraso_maximo >= 31 AND c.dias_atraso_maximo <= 90";
            } elseif ($faixa === '120') {
                $whereClauses[] = "c.dias_atraso_maximo >= 91";
            }
        }

        if (!empty($search)) {
            $whereClauses[] = "(c.razao_social ILIKE :search OR c.nome_fantasia ILIKE :search OR c.cnpj_cpf ILIKE :search OR c.email ILIKE :search OR c.telefone1_numero ILIKE :search)";
            $bindings['search'] = '%' . $search . '%';
        }

        $defaultStageSql = ($tab !== 'adimplentes' ? "'inadimplencia'" : "'pagamento_concluido'");
        $whereClauses[] = "COALESCE(ks.stage, {$defaultStageSql}) = :target_stage";
        $bindings['target_stage'] = $stageSlug;

        $whereSql = implode(" AND ", $whereClauses);

        $allowedSorts = [
            'name' => 'name',
            'email' => 'email',
            'stage' => 'stage',
            'divida' => ($tab === 'inadimplentes_redecard' ? 'divida_redecard' : 'divida_comum'),
            'atraso' => 'dias_atraso',
            'faixa' => 'dias_atraso',
            'phone' => 'phone'
        ];
        $sortColumn = $allowedSorts[$sortBy] ?? 'name';
        $orderSql = "ORDER BY {$sortColumn} " . (strtolower($sortDir) === 'desc' ? 'DESC' : 'ASC');

        $offset = ($page - 1) * $perPage;

        $queryStr = "
            SELECT 
                c.cnpj_cpf,
                MAX(c.razao_social) as name,
                MAX(c.email) as email,
                MAX(c.telefone1_numero) as phone,
                MAX(c.telefone1_ddd) as phone_ddd,
                string_agg(DISTINCT c.ulo_source, ',') as all_ulos,
                COALESCE(MAX(ks.stage), {$defaultStageSql}) as stage,
                MAX(c.divida_comum_total) as divida_comum,
                MAX(c.divida_redecard_total) as divida_redecard,
                MAX(c.qtd_titulos_comum) as qtd_titulos_comum,
                MAX(c.qtd_titulos_redecard) as qtd_titulos_redecard,
                MAX(c.dias_atraso_maximo) as dias_atraso
            FROM omie_clientes c
            LEFT JOIN client_kanban_stages ks ON c.cnpj_cpf = ks.cnpj_cpf
            WHERE {$whereSql}
            GROUP BY c.cnpj_cpf
            {$orderSql}
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $items = DB::select($queryStr, $bindings);

        // Fetch all kanban columns definition for card move menu
        $dbColumns = DB::table('kanban_columns')->orderBy('position', 'asc')->get();
        $kanbanColumns = [];
        foreach ($dbColumns as $col) {
            $kanbanColumns[$col->slug] = [
                'id' => $col->slug,
                'title' => mb_strtoupper($col->title),
                'dot_color' => $col->dot_color ?? 'bg-primary',
            ];
        }

        $html = '';
        foreach ($items as $cliente) {
            $html .= view('pages.clientes.partials.kanban-card', [
                'cliente' => $cliente,
                'colId' => $stageSlug,
                'kanbanColumns' => $kanbanColumns,
                'tab' => $tab
            ])->render();
        }

        $hasMore = count($items) === $perPage;

        return response()->json([
            'success' => true,
            'html' => $html,
            'count' => count($items),
            'has_more' => $hasMore,
            'next_page' => $page + 1
        ]);
    }
}
