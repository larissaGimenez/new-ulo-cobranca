<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OmieContaReceber;
use App\Models\OmieCliente;
use App\Models\OmieContaCorrente;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DevSettingsController extends Controller
{
    public function index()
    {
        // Get statistics of records per ULO Source
        $stats = OmieContaReceber::select('ulo_source', \DB::raw('count(*) as total'))
            ->groupBy('ulo_source')
            ->get()
            ->pluck('total', 'ulo_source')
            ->toArray();

        // Get ULOs list from env
        $ulos = [];
        for ($i = 1; $i <= 5; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            $name = env("ULO_{$num}_NAME");
            $key = env("ULO_{$num}_APP_KEY");
            
            if ($name && $key) {
                $ulos[] = [
                    'id' => $num,
                    'name' => $name,
                    'key' => $key,
                    'total_records' => $stats[$name] ?? 0
                ];
            }
        }

        return view('dev-settings', compact('ulos'));
    }

    public function syncPage(Request $request)
    {
        $request->validate([
            'ulo_id' => 'required|integer|between:1,5',
            'page' => 'required|integer|min:1',
        ]);

        $uloId = $request->input('ulo_id');
        $page = $request->input('page');

        $num = str_pad($uloId, 2, '0', STR_PAD_LEFT);
        $name = env("ULO_{$num}_NAME");
        $appKey = env("ULO_{$num}_APP_KEY");
        $appSecret = env("ULO_{$num}_APP_SECRET");

        if (empty($name) || empty($appKey) || empty($appSecret)) {
            return response()->json([
                'success' => false,
                'message' => "ULO {$num} não está configurada no .env.",
                'finished' => true
            ]);
        }

        try {
            $response = Http::timeout(30)->post('https://app.omie.com.br/api/v1/financas/contareceber/', [
                'call' => 'ListarContasReceber',
                'param' => [
                    [
                        'pagina' => $page,
                        'registros_por_pagina' => 100,
                        'apenas_importado_api' => 'N'
                    ]
                ],
                'app_key' => $appKey,
                'app_secret' => $appSecret
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => "Erro de API da Omie para a {$name} na página {$page}: Status " . $response->status() . " | Resposta: " . $response->body()
                ], 500);
            }

            $data = $response->json();
            
            if (isset($data['faultstring'])) {
                if (str_contains($data['faultstring'], 'Não existem registros para a página')) {
                    return response()->json([
                        'success' => true,
                        'ulo_name' => $name,
                        'page' => $page,
                        'total_pages' => $page - 1 ?: 1,
                        'imported_count' => 0,
                        'finished' => true
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'message' => "Erro Omie para {$name}: " . $data['faultstring']
                ], 400);
            }

            $totalPages = $data['total_de_paginas'] ?? 1;
            $records = $data['conta_receber_cadastro'] ?? [];
            $importedCount = 0;

            if (count($records) > 0) {
                $omieIds = array_column($records, 'codigo_lancamento_omie');
                $existing = OmieContaReceber::where('ulo_source', $name)
                    ->whereIn('codigo_lancamento_omie', $omieIds)
                    ->get()
                    ->keyBy('codigo_lancamento_omie');

                foreach ($records as $record) {
                    $omieId = $record['codigo_lancamento_omie'];
                    
                    if (isset($existing[$omieId])) {
                        $dbRecord = $existing[$omieId];
                        
                        $statusChanged = $dbRecord->status_titulo !== ($record['status_titulo'] ?? null);
                        $valueChanged = (float)$dbRecord->valor_documento !== (float)($record['valor_documento'] ?? 0);
                        $previsaoChanged = $dbRecord->data_previsao?->format('Y-m-d') !== $this->parseDate($record['data_previsao'] ?? null);
                        $contaCorrenteChanged = $dbRecord->id_conta_corrente !== ($record['id_conta_corrente'] ?? null);
                        
                        if (!$statusChanged && !$valueChanged && !$previsaoChanged && !$contaCorrenteChanged) {
                            continue;
                        }
                        
                        $dbRecord->update([
                            'codigo_cliente_fornecedor' => $record['codigo_cliente_fornecedor'],
                            'codigo_categoria' => $record['codigo_categoria'] ?? null,
                            'codigo_tipo_documento' => $record['codigo_tipo_documento'] ?? null,
                            'data_emissao' => $this->parseDate($record['data_emissao'] ?? null),
                            'data_vencimento' => $this->parseDate($record['data_vencimento'] ?? null),
                            'data_previsao' => $this->parseDate($record['data_previsao'] ?? null),
                            'data_registro' => $this->parseDate($record['data_registro'] ?? null),
                            'id_conta_corrente' => $record['id_conta_corrente'] ?? null,
                            'id_origem' => $record['id_origem'] ?? null,
                            'numero_parcela' => $record['numero_parcela'] ?? null,
                            'status_titulo' => $record['status_titulo'] ?? null,
                            'valor_documento' => $record['valor_documento'] ?? null,
                            'bloqueado' => $record['bloqueado'] ?? null,
                            'bloquear_baixa' => $record['bloquear_baixa'] ?? null,
                            'boleto' => $record['boleto'] ?? null,
                            'categorias' => $record['categorias'] ?? null,
                            'distribuicao' => $record['distribuicao'] ?? null,
                            'info' => $record['info'] ?? null,
                        ]);
                    } else {
                        OmieContaReceber::create([
                            'ulo_source' => $name,
                            'codigo_lancamento_omie' => $record['codigo_lancamento_omie'],
                            'codigo_cliente_fornecedor' => $record['codigo_cliente_fornecedor'],
                            'codigo_categoria' => $record['codigo_categoria'] ?? null,
                            'codigo_tipo_documento' => $record['codigo_tipo_documento'] ?? null,
                            'data_emissao' => $this->parseDate($record['data_emissao'] ?? null),
                            'data_vencimento' => $this->parseDate($record['data_vencimento'] ?? null),
                            'data_previsao' => $this->parseDate($record['data_previsao'] ?? null),
                            'data_registro' => $this->parseDate($record['data_registro'] ?? null),
                            'id_conta_corrente' => $record['id_conta_corrente'] ?? null,
                            'id_origem' => $record['id_origem'] ?? null,
                            'numero_parcela' => $record['numero_parcela'] ?? null,
                            'status_titulo' => $record['status_titulo'] ?? null,
                            'valor_documento' => $record['valor_documento'] ?? null,
                            'bloqueado' => $record['bloqueado'] ?? null,
                            'bloquear_baixa' => $record['bloquear_baixa'] ?? null,
                            'boleto' => $record['boleto'] ?? null,
                            'categorias' => $record['categorias'] ?? null,
                            'distribuicao' => $record['distribuicao'] ?? null,
                            'info' => $record['info'] ?? null,
                        ]);
                    }
                    $importedCount++;
                }
            }

            $finished = ($page >= $totalPages);

            return response()->json([
                'success' => true,
                'ulo_name' => $name,
                'page' => $page,
                'total_pages' => $totalPages,
                'imported_count' => $importedCount,
                'finished' => $finished
            ]);
        } catch (\Exception $e) {
            Log::error("Omie Sync Error for {$name} page {$page}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Erro na sincronização de {$name}: " . $e->getMessage() . " em " . basename($e->getFile()) . ":" . $e->getLine() . "\nStacktrace:\n" . substr($e->getTraceAsString(), 0, 1000)
            ], 500);
        }
    }

    public function clientesIndex()
    {
        // Get statistics of records per ULO Source for Clientes
        $stats = OmieCliente::select('ulo_source', \DB::raw('count(*) as total'))
            ->groupBy('ulo_source')
            ->get()
            ->pluck('total', 'ulo_source')
            ->toArray();

        // Get ULOs list from env
        $ulos = [];
        for ($i = 1; $i <= 5; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            $name = env("ULO_{$num}_NAME");
            $key = env("ULO_{$num}_APP_KEY");
            
            if ($name && $key) {
                $ulos[] = [
                    'id' => $num,
                    'name' => $name,
                    'key' => $key,
                    'total_records' => $stats[$name] ?? 0
                ];
            }
        }

        return view('dev-settings-clientes', compact('ulos'));
    }

    public function syncClientesPage(Request $request)
    {
        $request->validate([
            'ulo_id' => 'required|integer|between:1,5',
            'page' => 'required|integer|min:1',
        ]);

        $uloId = $request->input('ulo_id');
        $page = $request->input('page');

        $num = str_pad($uloId, 2, '0', STR_PAD_LEFT);
        $name = env("ULO_{$num}_NAME");
        $appKey = env("ULO_{$num}_APP_KEY");
        $appSecret = env("ULO_{$num}_APP_SECRET");

        if (empty($name) || empty($appKey) || empty($appSecret)) {
            return response()->json([
                'success' => false,
                'message' => "ULO {$num} não está configurada no .env.",
                'finished' => true
            ]);
        }

        try {
            $response = Http::timeout(30)->post('https://app.omie.com.br/api/v1/geral/clientes/', [
                'call' => 'ListarClientes',
                'param' => [
                    [
                        'pagina' => $page,
                        'registros_por_pagina' => 50,
                        'apenas_importado_api' => 'N'
                    ]
                ],
                'app_key' => $appKey,
                'app_secret' => $appSecret
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => "Erro de API da Omie para a {$name} na página {$page}: Status " . $response->status() . " | Resposta: " . $response->body()
                ], 500);
            }

            $data = $response->json();
            
            if (isset($data['faultstring'])) {
                if (str_contains($data['faultstring'], 'Não existem registros para a página')) {
                    return response()->json([
                        'success' => true,
                        'ulo_name' => $name,
                        'page' => $page,
                        'total_pages' => $page - 1 ?: 1,
                        'imported_count' => 0,
                        'finished' => true
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'message' => "Erro Omie para {$name}: " . $data['faultstring']
                ], 400);
            }

            $totalPages = $data['total_de_paginas'] ?? 1;
            $records = $data['clientes_cadastro'] ?? [];
            $importedCount = 0;

            if (count($records) > 0) {
                $clientOmieIds = array_column($records, 'codigo_cliente_omie');
                $existing = OmieCliente::where('ulo_source', $name)
                    ->whereIn('codigo_cliente_omie', $clientOmieIds)
                    ->get()
                    ->keyBy('codigo_cliente_omie');

                foreach ($records as $record) {
                    $omieId = $record['codigo_cliente_omie'];
                    
                    if (isset($existing[$omieId])) {
                        $dbRecord = $existing[$omieId];
                        
                        $nameChanged = $dbRecord->razao_social !== ($record['razao_social'] ?: ($record['nome_fantasia'] ?: ''));
                        $cnpjChanged = $dbRecord->cnpj_cpf !== $record['cnpj_cpf'];
                        $inativoChanged = $dbRecord->inativo !== ($record['inativo'] ?? 'N');
                        $phoneChanged = $dbRecord->telefone1_numero !== ($record['telefone1_numero'] ?? null);
                        
                        if (!$nameChanged && !$cnpjChanged && !$inativoChanged && !$phoneChanged) {
                            continue;
                        }
                        
                        $dbRecord->update([
                            'codigo_cliente_integracao' => $record['codigo_cliente_integracao'] ?? null,
                            'cnpj_cpf' => $record['cnpj_cpf'],
                            'razao_social' => $record['razao_social'] ?: ($record['nome_fantasia'] ?: ''),
                            'nome_fantasia' => $record['nome_fantasia'] ?: ($record['razao_social'] ?: ''),
                            'bairro' => $record['bairro'] ?? null,
                            'cep' => $record['cep'] ?? null,
                            'cidade' => $record['cidade'] ?? null,
                            'cidade_ibge' => $record['cidade_ibge'] ?? null,
                            'estado' => $record['estado'] ?? null,
                            'endereco' => $record['endereco'] ?? null,
                            'endereco_numero' => $record['endereco_numero'] ?? null,
                            'complemento' => $record['complemento'] ?? null,
                            'email' => $record['email'] ?? null,
                            'telefone1_ddd' => $record['telefone1_ddd'] ?? null,
                            'telefone1_numero' => $record['telefone1_numero'] ?? null,
                            'inativo' => $record['inativo'] ?? 'N',
                            'pessoa_fisica' => $record['pessoa_fisica'] ?? 'N',
                            'dadosBancarios' => $record['dadosBancarios'] ?? null,
                            'recomendacoes' => $record['recomendacoes'] ?? null,
                            'tags' => $record['tags'] ?? null,
                            'info' => $record['info'] ?? null,
                        ]);
                    } else {
                        OmieCliente::create([
                            'ulo_source' => $name,
                            'codigo_cliente_omie' => $record['codigo_cliente_omie'],
                            'codigo_cliente_integracao' => $record['codigo_cliente_integracao'] ?? null,
                            'cnpj_cpf' => $record['cnpj_cpf'],
                            'razao_social' => $record['razao_social'] ?: ($record['nome_fantasia'] ?: ''),
                            'nome_fantasia' => $record['nome_fantasia'] ?: ($record['razao_social'] ?: ''),
                            'bairro' => $record['bairro'] ?? null,
                            'cep' => $record['cep'] ?? null,
                            'cidade' => $record['cidade'] ?? null,
                            'cidade_ibge' => $record['cidade_ibge'] ?? null,
                            'estado' => $record['estado'] ?? null,
                            'endereco' => $record['endereco'] ?? null,
                            'endereco_numero' => $record['endereco_numero'] ?? null,
                            'complemento' => $record['complemento'] ?? null,
                            'email' => $record['email'] ?? null,
                            'telefone1_ddd' => $record['telefone1_ddd'] ?? null,
                            'telefone1_numero' => $record['telefone1_numero'] ?? null,
                            'inativo' => $record['inativo'] ?? 'N',
                            'pessoa_fisica' => $record['pessoa_fisica'] ?? 'N',
                            'dadosBancarios' => $record['dadosBancarios'] ?? null,
                            'recomendacoes' => $record['recomendacoes'] ?? null,
                            'tags' => $record['tags'] ?? null,
                            'info' => $record['info'] ?? null,
                        ]);
                    }
                    $importedCount++;
                }
            }

            $finished = ($page >= $totalPages);

            return response()->json([
                'success' => true,
                'ulo_name' => $name,
                'page' => $page,
                'total_pages' => $totalPages,
                'imported_count' => $importedCount,
                'finished' => $finished
            ]);
        } catch (\Exception $e) {
            Log::error("Omie Clientes Sync Error for {$name} page {$page}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Erro na sincronização de {$name}: " . $e->getMessage() . " em " . basename($e->getFile()) . ":" . $e->getLine() . "\nStacktrace:\n" . substr($e->getTraceAsString(), 0, 1000)
            ], 500);
        }
    }

    public function vinculosIndex()
    {
        $totalTitles = OmieContaReceber::count();
        
        $linkedTitles = OmieContaReceber::join('omie_clientes', function ($join) {
            $join->on('omie_contas_receber.codigo_cliente_fornecedor', '=', 'omie_clientes.codigo_cliente_omie')
                 ->on('omie_contas_receber.ulo_source', '=', 'omie_clientes.ulo_source');
        })->count();

        $orphanTitlesCount = $totalTitles - $linkedTitles;

        // Buscar títulos órfãos paginados
        $orphans = OmieContaReceber::leftJoin('omie_clientes', function ($join) {
            $join->on('omie_contas_receber.codigo_cliente_fornecedor', '=', 'omie_clientes.codigo_cliente_omie')
                 ->on('omie_contas_receber.ulo_source', '=', 'omie_clientes.ulo_source');
        })
        ->whereNull('omie_clientes.id')
        ->select('omie_contas_receber.*')
        ->paginate(15);

        // Statistics per ULO
        $statsByUlo = [];
        for ($i = 1; $i <= 5; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            $name = env("ULO_{$num}_NAME");
            if ($name) {
                $total = OmieContaReceber::where('ulo_source', $name)->count();
                
                $linked = OmieContaReceber::where('omie_contas_receber.ulo_source', $name)
                    ->join('omie_clientes', function ($join) {
                        $join->on('omie_contas_receber.codigo_cliente_fornecedor', '=', 'omie_clientes.codigo_cliente_omie')
                             ->on('omie_contas_receber.ulo_source', '=', 'omie_clientes.ulo_source');
                    })->count();

                $statsByUlo[] = [
                    'name' => $name,
                    'total' => $total,
                    'linked' => $linked,
                    'orphan' => $total - $linked
                ];
            }
        }

        return view('dev-settings-vinculos', compact('totalTitles', 'linkedTitles', 'orphanTitlesCount', 'statsByUlo', 'orphans'));
    }

    public function contasIndex()
    {
        // Get statistics of records per ULO Source for Contas Correntes
        $stats = OmieContaCorrente::select('ulo_source', \DB::raw('count(*) as total'))
            ->groupBy('ulo_source')
            ->get()
            ->pluck('total', 'ulo_source')
            ->toArray();

        // Get ULOs list from env
        $ulos = [];
        for ($i = 1; $i <= 5; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            $name = env("ULO_{$num}_NAME");
            $key = env("ULO_{$num}_APP_KEY");
            
            if ($name && $key) {
                $ulos[] = [
                    'id' => $num,
                    'name' => $name,
                    'key' => $key,
                    'total_records' => $stats[$name] ?? 0
                ];
            }
        }

        return view('dev-settings-contas', compact('ulos'));
    }

    public function syncContasPage(Request $request)
    {
        $request->validate([
            'ulo_id' => 'required|integer|between:1,5',
            'page' => 'required|integer|min:1',
        ]);

        $uloId = $request->input('ulo_id');
        $page = $request->input('page');

        $num = str_pad($uloId, 2, '0', STR_PAD_LEFT);
        $name = env("ULO_{$num}_NAME");
        $appKey = env("ULO_{$num}_APP_KEY");
        $appSecret = env("ULO_{$num}_APP_SECRET");

        if (empty($name) || empty($appKey) || empty($appSecret)) {
            return response()->json([
                'success' => false,
                'message' => "ULO {$num} não está configurada no .env.",
                'finished' => true
            ]);
        }

        try {
            $response = Http::timeout(30)->post('https://app.omie.com.br/api/v1/geral/contacorrente/', [
                'call' => 'ListarResumoContasCorrentes',
                'param' => [
                    [
                        'pagina' => $page,
                        'registros_por_pagina' => 100,
                        'apenas_importado_api' => 'N'
                    ]
                ],
                'app_key' => $appKey,
                'app_secret' => $appSecret
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => "Erro de API da Omie para a {$name} na página {$page}: Status " . $response->status() . " | Resposta: " . $response->body()
                ], 500);
            }

            $data = $response->json();
            
            if (isset($data['faultstring'])) {
                if (str_contains($data['faultstring'], 'Não existem registros para a página')) {
                    return response()->json([
                        'success' => true,
                        'ulo_name' => $name,
                        'page' => $page,
                        'total_pages' => $page - 1 ?: 1,
                        'imported_count' => 0,
                        'finished' => true
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'message' => "Erro Omie para {$name}: " . $data['faultstring']
                ], 400);
            }

            $totalPages = $data['total_de_paginas'] ?? 1;
            $records = $data['conta_corrente_lista'] ?? [];
            $importedCount = 0;

            foreach ($records as $record) {
                OmieContaCorrente::updateOrCreate(
                    [
                        'ulo_source' => $name,
                        'n_cod_c_c' => $record['nCodCC']
                    ],
                    [
                        'descricao' => $record['descricao'],
                        'codigo_banco' => $record['codigo_banco'] ?? null,
                        'tipo' => $record['tipo'] ?? null,
                        'codigo_agencia' => $record['codigo_agencia'] ?? null,
                        'conta_corrente' => $record['conta_corrente'] ?? null,
                        'c_cod_c_c_int' => $record['cCodCCInt'] ?? null,
                        'c_sincr_analitica' => $record['cSincrAnalitica'] ?? null,
                    ]
                );
                $importedCount++;
            }

            $finished = ($page >= $totalPages);

            return response()->json([
                'success' => true,
                'ulo_name' => $name,
                'page' => $page,
                'total_pages' => $totalPages,
                'imported_count' => $importedCount,
                'finished' => $finished
            ]);
        } catch (\Exception $e) {
            Log::error("Omie Contas Sync Error for {$name} page {$page}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Erro na sincronização de {$name}: " . $e->getMessage() . " em " . basename($e->getFile()) . ":" . $e->getLine() . "\nStacktrace:\n" . substr($e->getTraceAsString(), 0, 1000)
            ], 500);
        }
    }

    public function acompanhamentoIndex(\App\Services\SystemMonitorManager $manager)
    {
        $reverbOnline = $manager->isReverbOnline();
        $queueOnline = $manager->isQueueWorkerOnline();
        $scheduler = $manager->getSchedulerStatus();
        return view('dev-settings-acompanhamento', compact('reverbOnline', 'queueOnline', 'scheduler'));
    }

    public function logsIndex()
    {
        $logs = \App\Models\OmieChangeLog::latest()->paginate(25);
        return view('dev-settings-logs-webhooks', compact('logs'));
    }

    public function importOrphanClient(Request $request)
    {
        $request->validate([
            'ulo_source' => 'required|string',
            'codigo_cliente_omie' => 'required|numeric',
        ]);

        $uloSource = $request->input('ulo_source');
        $codigoClienteOmie = $request->input('codigo_cliente_omie');

        $appKey = null;
        $appSecret = null;
        for ($i = 1; $i <= 5; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            if (env("ULO_{$num}_NAME") === $uloSource) {
                $appKey = env("ULO_{$num}_APP_KEY");
                $appSecret = env("ULO_{$num}_APP_SECRET");
                break;
            }
        }

        if (!$appKey || !$appSecret) {
            return response()->json([
                'success' => false,
                'message' => "Credenciais para {$uloSource} não foram configuradas no arquivo .env."
            ], 400);
        }

        try {
            $response = Http::timeout(30)->post('https://app.omie.com.br/api/v1/geral/clientes/', [
                'call' => 'ConsultarCliente',
                'param' => [
                    [
                        'codigo_cliente_omie' => (int) $codigoClienteOmie
                    ]
                ],
                'app_key' => $appKey,
                'app_secret' => $appSecret
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => "Erro de API da Omie para a {$uloSource}: Status " . $response->status() . " | " . $response->body()
                ], 500);
            }

            $data = $response->json();

            if (isset($data['faultstring'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Erro da API Omie: " . $data['faultstring']
                ], 400);
            }

            OmieCliente::updateOrCreate(
                [
                    'ulo_source' => $uloSource,
                    'codigo_cliente_omie' => $data['codigo_cliente_omie']
                ],
                [
                    'codigo_cliente_integracao' => $data['codigo_cliente_integracao'] ?? null,
                    'cnpj_cpf' => $data['cnpj_cpf'] ?? '',
                    'razao_social' => $data['razao_social'] ?: ($data['nome_fantasia'] ?: ''),
                    'nome_fantasia' => $data['nome_fantasia'] ?: ($data['razao_social'] ?: ''),
                    'bairro' => $data['bairro'] ?? null,
                    'cep' => $data['cep'] ?? null,
                    'cidade' => $data['cidade'] ?? null,
                    'cidade_ibge' => $data['cidade_ibge'] ?? null,
                    'estado' => $data['estado'] ?? null,
                    'endereco' => $data['endereco'] ?? null,
                    'endereco_numero' => $data['endereco_numero'] ?? null,
                    'complemento' => $data['complemento'] ?? null,
                    'inativo' => $data['inativo'] ?? 'N',
                    'telefone1_ddd' => $data['telefone1_ddd'] ?? ($data['telefone2_ddd'] ?? null),
                    'telefone1_numero' => $data['telefone1_numero'] ?? ($data['telefone2_numero'] ?? null),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Cliente " . ($data['razao_social'] ?: $data['nome_fantasia']) . " importado e vinculado com sucesso!"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Erro ao importar cliente: " . $e->getMessage()
            ], 500);
        }
    }

    public function searchClients(Request $request)
    {
        $search = $request->input('q');
        $uloSource = $request->input('ulo_source');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $clients = OmieCliente::where('ulo_source', $uloSource)
            ->where(function ($query) use ($search) {
                $query->where('razao_social', 'like', "%{$search}%")
                      ->orWhere('nome_fantasia', 'like', "%{$search}%")
                      ->orWhere('cnpj_cpf', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get(['codigo_cliente_omie', 'razao_social', 'nome_fantasia', 'cnpj_cpf']);

        return response()->json($clients);
    }

    public function reassociateTitle(Request $request)
    {
        $request->validate([
            'title_id' => 'required|exists:omie_contas_receber,id',
            'new_client_code' => 'required|numeric',
        ]);

        $title = OmieContaReceber::findOrFail($request->input('title_id'));
        $oldClientCode = $title->codigo_cliente_fornecedor;
        $title->update([
            'codigo_cliente_fornecedor' => $request->input('new_client_code')
        ]);

        return response()->json([
            'success' => true,
            'message' => "Título #{$title->codigo_lancamento_omie} reassociado com sucesso para o novo cliente!"
        ]);
    }

    public function suggestReassociation($id)
    {
        $title = OmieContaReceber::findOrFail($id);
        $oldCode = $title->codigo_cliente_fornecedor;
        
        $num = null;
        for ($i = 1; $i <= 5; $i++) {
            $numPad = str_pad($i, 2, '0', STR_PAD_LEFT);
            if (env("ULO_{$numPad}_NAME") === $title->ulo_source) {
                $num = $numPad;
                break;
            }
        }
        
        if (!$num) {
            return response()->json(['success' => false, 'message' => 'ULO não configurada no arquivo de ambiente (.env)']);
        }
        
        $appKey = env("ULO_{$num}_APP_KEY");
        $appSecret = env("ULO_{$num}_APP_SECRET");
        
        try {
            // 1. Consultar a conta a receber para obter o nCodOS
            $titleResponse = Http::timeout(15)->post('https://app.omie.com.br/api/v1/financas/contareceber/', [
                'call' => 'ConsultarContaReceber',
                'param' => [['codigo_lancamento_omie' => (int) $title->codigo_lancamento_omie]],
                'app_key' => $appKey,
                'app_secret' => $appSecret
            ]);
            
            if ($titleResponse->failed()) {
                return response()->json(['success' => false, 'message' => 'Título não encontrado no Omie']);
            }
            
            $titleData = $titleResponse->json();
            $nCodOS = $titleData['nCodOS'] ?? null;
            
            if (!$nCodOS) {
                return response()->json([
                    'success' => true,
                    'found' => false,
                    'message' => 'Este título não possui nenhuma Ordem de Serviço vinculada no Omie para rastreamento.'
                ]);
            }
            
            // 2. Consultar a OS correspondente
            $osResponse = Http::timeout(15)->post('https://app.omie.com.br/api/v1/servicos/os/', [
                'call' => 'ConsultarOS',
                'param' => [['nCodOS' => (int) $nCodOS]],
                'app_key' => $appKey,
                'app_secret' => $appSecret
            ]);
            
            if ($osResponse->failed()) {
                return response()->json(['success' => false, 'message' => 'Ordem de Serviço do título não encontrada no Omie']);
            }
            
            $osData = $osResponse->json();
            $cObsOS = $osData['Observacoes']['cObsOS'] ?? '';
            $osNumber = $osData['Cabecalho']['cNumOS'] ?? '';
            
            // 3. Extrair o nome do cliente original do cabeçalho de observações da OS
            $clientName = '';
            if ($cObsOS) {
                $parts = explode('|', $cObsOS);
                $firstPart = trim($parts[0]);
                // Remove prefixos comuns de envio
                $clientName = preg_replace('/^envio:?\s+\d{2}\/\d{2}\/\d{2,4}\s+/i', '', $firstPart);
                $clientName = trim($clientName);
            }
            
            if (empty($clientName)) {
                return response()->json([
                    'success' => true,
                    'found' => false,
                    'message' => 'Não foi possível ler o nome do cliente nas observações da Ordem de Serviço.'
                ]);
            }
            
            // 4. Buscar cliente correspondente localmente no banco
            $localClient = OmieCliente::where('razao_social', 'like', "%{$clientName}%")
                ->orWhere('nome_fantasia', 'like', "%{$clientName}%")
                ->first();
                
            if ($localClient) {
                return response()->json([
                    'success' => true,
                    'found' => true,
                    'client_name' => $localClient->razao_social,
                    'client_code' => $localClient->codigo_cliente_omie,
                    'old_code' => $oldCode,
                    'os_number' => $osNumber,
                    'os_id' => $nCodOS,
                    'ai_diagnosis' => "Identificamos que o cadastro do cliente original foi removido ou alterado no Omie. Ao inspecionar a Ordem de Serviço #{$osNumber} vinculada a este título, a inteligência localizou o cadastro correspondente de {$localClient->razao_social} sob o código Omie {$localClient->codigo_cliente_omie} no banco local."
                ]);
            }
            
            // 5. Buscar cliente correspondente no Omie varrendo as ULOs (1 a 5)
            for ($u = 1; $u <= 5; $u++) {
                $uloNum = str_pad($u, 2, '0', STR_PAD_LEFT);
                $uloName = env("ULO_{$uloNum}_NAME");
                $uloKey = env("ULO_{$uloNum}_APP_KEY");
                $uloSecret = env("ULO_{$uloNum}_APP_SECRET");
                
                if (!$uloName || !$uloKey) continue;
                
                // Vamos varrer até 10 páginas de cada ULO para evitar timeouts excessivos
                for ($p = 1; $p <= 10; $p++) {
                    $searchResponse = Http::timeout(10)->post('https://app.omie.com.br/api/v1/geral/clientes/', [
                        'call' => 'ListarClientes',
                        'param' => [
                            [
                                'pagina' => $p,
                                'registros_por_pagina' => 100,
                                'apenas_importado_api' => 'N'
                            ]
                        ],
                        'app_key' => $uloKey,
                        'app_secret' => $uloSecret
                    ]);
                    
                    if ($searchResponse->failed()) {
                        break;
                    }
                    
                    $searchData = $searchResponse->json();
                    if (!isset($searchData['clientes_cadastro']) || count($searchData['clientes_cadastro']) === 0) {
                        break;
                    }
                    
                    foreach ($searchData['clientes_cadastro'] as $c) {
                        $razao = $c['razao_social'] ?? '';
                        $fantasia = $c['nome_fantasia'] ?? '';
                        
                        if (str_contains(strtolower($razao), strtolower($clientName)) || str_contains(strtolower($fantasia), strtolower($clientName))) {
                            
                            // Cria ou atualiza o cliente localmente no banco para que o vínculo funcione
                            $newClient = OmieCliente::updateOrCreate(
                                [
                                    'ulo_source' => $uloName,
                                    'codigo_cliente_omie' => $c['codigo_cliente_omie']
                                ],
                                [
                                    'codigo_cliente_integracao' => $c['codigo_cliente_integracao'] ?? null,
                                    'cnpj_cpf' => $c['cnpj_cpf'] ?? '',
                                    'razao_social' => $c['razao_social'] ?: ($c['nome_fantasia'] ?: ''),
                                    'nome_fantasia' => $c['nome_fantasia'] ?: ($c['razao_social'] ?: ''),
                                    'bairro' => $c['bairro'] ?? null,
                                    'cep' => $c['cep'] ?? null,
                                    'cidade' => $c['cidade'] ?? null,
                                    'cidade_ibge' => $c['cidade_ibge'] ?? null,
                                    'estado' => $c['estado'] ?? null,
                                    'endereco' => $c['endereco'] ?? null,
                                    'endereco_numero' => $c['endereco_numero'] ?? null,
                                    'complemento' => $c['complemento'] ?? null,
                                    'inativo' => $c['inativo'] ?? 'N',
                                    'email' => $c['email'] ?? null,
                                    'telefone1_ddd' => $c['telefone1_ddd'] ?? ($c['telefone2_ddd'] ?? null),
                                    'telefone1_numero' => $c['telefone1_numero'] ?? ($c['telefone2_numero'] ?? null),
                                ]
                            );
                            
                            return response()->json([
                                'success' => true,
                                'found' => true,
                                'client_name' => $c['razao_social'],
                                'client_code' => $c['codigo_cliente_omie'],
                                'old_code' => $oldCode,
                                'os_number' => $osNumber,
                                'os_id' => $nCodOS,
                                'ai_diagnosis' => "Identificamos que o cadastro do cliente original foi removido ou alterado no Omie. Ao inspecionar a Ordem de Serviço #{$osNumber} vinculada a este título, a inteligência localizou o novo cadastro ativo de {$c['razao_social']} sob o código Omie {$c['codigo_cliente_omie']} na ULO {$uloName}."
                            ]);
                        }
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'found' => false,
                'message' => "Detectamos pelas observações da OS que o cliente é '{$clientName}', mas nenhum cadastro correspondente a este nome foi localizado no banco de dados ou nos servidores do Omie."
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro na detecção automática: ' . $e->getMessage()
            ]);
        }
    }

    private function parseDate($dateStr)
    {
        if (empty($dateStr)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('d/m/Y', $dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
