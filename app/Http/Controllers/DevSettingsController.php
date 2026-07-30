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

        $orphanTitles = $totalTitles - $linkedTitles;

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

        return view('dev-settings-vinculos', compact('totalTitles', 'linkedTitles', 'orphanTitles', 'statsByUlo'));
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
