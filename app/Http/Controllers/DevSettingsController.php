<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OmieContaPagar;
use App\Models\OmieCliente;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DevSettingsController extends Controller
{
    public function index()
    {
        // Get statistics of records per ULO Source
        $stats = OmieContaPagar::select('ulo_source', \DB::raw('count(*) as total'))
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
            $response = Http::timeout(30)->post('https://app.omie.com.br/api/v1/financas/contapagar/', [
                'call' => 'ListarContasPagar',
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
                    'message' => "Erro de API da Omie para a {$name} na página {$page}: Status " . $response->status()
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
            $records = $data['conta_pagar_cadastro'] ?? [];
            $importedCount = 0;

            foreach ($records as $record) {
                OmieContaPagar::updateOrCreate(
                    [
                        'ulo_source' => $name,
                        'codigo_lancamento_omie' => $record['codigo_lancamento_omie']
                    ],
                    [
                        'codigo_cliente_fornecedor' => $record['codigo_cliente_fornecedor'] ?? null,
                        'codigo_categoria' => $record['codigo_categoria'] ?? null,
                        'codigo_tipo_documento' => $record['codigo_tipo_documento'] ?? null,
                        'data_emissao' => $this->parseDate($record['data_emissao'] ?? null),
                        'data_entrada' => $this->parseDate($record['data_entrada'] ?? null),
                        'data_previsao' => $this->parseDate($record['data_previsao'] ?? null),
                        'data_vencimento' => $this->parseDate($record['data_vencimento'] ?? null),
                        'id_conta_corrente' => $record['id_conta_corrente'] ?? null,
                        'id_origem' => $record['id_origem'] ?? null,
                        'numero_documento' => $record['numero_documento'] ?? null,
                        'numero_documento_fiscal' => $record['numero_documento_fiscal'] ?? null,
                        'numero_parcela' => $record['numero_parcela'] ?? null,
                        'status_titulo' => $record['status_titulo'] ?? null,
                        'valor_documento' => $record['valor_documento'] ?? null,
                        'chave_nfe' => $record['chave_nfe'] ?? null,
                        'operacao' => $record['operacao'] ?? null,
                        'baixa_bloqueada' => $record['baixa_bloqueada'] ?? null,
                        'bloqueado' => $record['bloqueado'] ?? null,
                        'codigo_barras_ficha_compensacao' => $record['codigo_barras_ficha_compensacao'] ?? null,
                        'retem_cofins' => $record['retem_cofins'] ?? null,
                        'retem_csll' => $record['retem_csll'] ?? null,
                        'retem_inss' => $record['retem_inss'] ?? null,
                        'retem_ir' => $record['retem_ir'] ?? null,
                        'retem_iss' => $record['retem_iss'] ?? null,
                        'retem_pis' => $record['retem_pis'] ?? null,
                        'info' => $record['info'] ?? null,
                        'categorias' => $record['categorias'] ?? null,
                        'cnab_integracao_bancaria' => $record['cnab_integracao_bancaria'] ?? null,
                        'distribuicao' => $record['distribuicao'] ?? null,
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
            Log::error("Omie Sync Error for {$name} page {$page}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Erro na sincronização de {$name}: " . $e->getMessage()
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
                    'message' => "Erro de API da Omie para a {$name} na página {$page}: Status " . $response->status()
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

            foreach ($records as $record) {
                OmieCliente::updateOrCreate(
                    [
                        'ulo_source' => $name,
                        'codigo_cliente_omie' => $record['codigo_cliente_omie']
                    ],
                    [
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
            Log::error("Omie Clientes Sync Error for {$name} page {$page}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Erro na sincronização de {$name}: " . $e->getMessage()
            ], 500);
        }
    }

    public function vinculosIndex()
    {
        $totalTitles = OmieContaPagar::count();
        
        $linkedTitles = OmieContaPagar::join('omie_clientes', function ($join) {
            $join->on('omie_contas_pagar.codigo_cliente_fornecedor', '=', 'omie_clientes.codigo_cliente_omie')
                 ->on('omie_contas_pagar.ulo_source', '=', 'omie_clientes.ulo_source');
        })->count();

        $orphanTitles = $totalTitles - $linkedTitles;

        // Statistics per ULO
        $statsByUlo = [];
        for ($i = 1; $i <= 5; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            $name = env("ULO_{$num}_NAME");
            if ($name) {
                $total = OmieContaPagar::where('ulo_source', $name)->count();
                
                $linked = OmieContaPagar::where('omie_contas_pagar.ulo_source', $name)
                    ->join('omie_clientes', function ($join) {
                        $join->on('omie_contas_pagar.codigo_cliente_fornecedor', '=', 'omie_clientes.codigo_cliente_omie')
                             ->on('omie_contas_pagar.ulo_source', '=', 'omie_clientes.ulo_source');
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
