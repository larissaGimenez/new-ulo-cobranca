<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OmieContaReceber;
use App\Models\OmieCliente;
use App\Models\OmieChangeLog;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OmieWebhookController extends Controller
{
    /**
     * Trata o recebimento do Webhook enviado pelo Omie.
     */
    public function handle(Request $request)
    {
        $appKey = $request->input('appKey');
        $uloSource = $this->getUloSourceByAppKey($appKey);

        if (!$uloSource) {
            Log::warning("Omie Webhook recebido para App Key não configurada: {$appKey}");
            return response()->json([
                'message' => 'Sucesso.',
                'errors' => []
            ]);
        }

        $topic = $request->input('topic');
        $event = $request->input('event');

        if (empty($topic) || empty($event)) {
            return response()->json([
                'message' => 'Payload inválido.',
                'errors' => ['Payload' => 'Campos topic ou event ausentes.']
            ], 400);
        }

        try {
            // Registrar log permanente na tabela de auditoria omie_change_logs
            OmieChangeLog::create([
                'ulo_source' => $uloSource,
                'entity_type' => str_contains(strtolower($topic), 'contareceber') ? 'receivable' : 'client',
                'entity_id' => str_contains(strtolower($topic), 'contareceber')
                    ? ($event['codigo_lancamento_omie'] ?? ($event[0]['conta_a_receber'][0]['codigo_lancamento_omie'] ?? 0))
                    : ($event['codigo_cliente_omie'] ?? 0),
                'action' => strtolower(explode('.', $topic)[2] ?? 'desconhecido'),
                'details' => $event,
            ]);

            // Direciona o processamento conforme a entidade
            if (str_contains(strtolower($topic), 'contareceber')) {
                $this->handleReceivable($uloSource, $topic, $event);
            } elseif (str_contains(strtolower($topic), 'clientefornecedor')) {
                $this->handleClient($uloSource, $topic, $event);
            }
        } catch (\Exception $e) {
            Log::error("Erro ao processar Omie Webhook ($topic): " . $e->getMessage());
        }

        // Retorna sucesso de forma ágil para evitar timeouts de entrega do Omie
        return response()->json([
            'message' => 'Sucesso.',
            'errors' => []
        ]);
    }

    /**
     * Mapeia a appKey do webhook para o nome amigável da ULO correspondente.
     */
    private function getUloSourceByAppKey($appKey)
    {
        for ($i = 1; $i <= 5; $i++) {
            $num = str_pad($i, 2, '0', STR_PAD_LEFT);
            if (env("ULO_{$num}_APP_KEY") == $appKey) {
                return env("ULO_{$num}_NAME");
            }
        }
        return null;
    }

    /**
     * Auxiliar de conversão de data ISO 8601 para Y-m-d.
     */
    private function parseDate($dateStr)
    {
        if (empty($dateStr)) {
            return null;
        }
        try {
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Calcula o status_titulo da conta a receber com base nos metadados.
     */
    private function calculateStatus($situacao, $previsaoDate)
    {
        $situacaoLower = mb_strtolower($situacao ?? '');
        if (str_contains($situacaoLower, 'recebido') || str_contains($situacaoLower, 'liquidado') || str_contains($situacaoLower, 'pago')) {
            return 'RECEBIDO';
        }
        if (str_contains($situacaoLower, 'cancelado')) {
            return 'CANCELADO';
        }

        if (empty($previsaoDate)) {
            return 'A VENCER';
        }

        $today = Carbon::today()->format('Y-m-d');
        if ($previsaoDate < $today) {
            return 'ATRASADO';
        } elseif ($previsaoDate === $today) {
            return 'VENCE HOJE';
        } else {
            return 'A VENCER';
        }
    }

    /**
     * Lógica de processamento para Contas a Receber.
     */
    private function handleReceivable($uloSource, $topic, $event)
    {
        $topicLower = strtolower($topic);

        if (str_contains($topicLower, 'incluido') || str_contains($topicLower, 'alterado') || str_contains($topicLower, 'boletogerado')) {
            $previsao = $this->parseDate($event['data_previsao'] ?? null);
            $status = $this->calculateStatus($event['situacao'] ?? null, $previsao);

            OmieContaReceber::updateOrCreate(
                [
                    'ulo_source' => $uloSource,
                    'codigo_lancamento_omie' => $event['codigo_lancamento_omie']
                ],
                [
                    'codigo_cliente_fornecedor' => $event['codigo_cliente_fornecedor'],
                    'codigo_categoria' => $event['codigo_categoria'] ?? null,
                    'codigo_tipo_documento' => $event['codigo_tipo_documento'] ?? null,
                    'data_emissao' => $this->parseDate($event['data_emissao'] ?? null),
                    'data_vencimento' => $this->parseDate($event['data_vencimento'] ?? null),
                    'data_previsao' => $previsao,
                    'data_registro' => $this->parseDate($event['data_registro'] ?? null),
                    'id_conta_corrente' => $event['id_conta_corrente'] ?? null,
                    'id_origem' => $event['id_origem'] ?? null,
                    'numero_parcela' => $event['numero_parcela'] ?? null,
                    'status_titulo' => $status,
                    'valor_documento' => $event['valor_documento'] ?? null,
                    'bloqueado' => $event['bloqueado'] ?? null,
                    'bloquear_baixa' => $event['baixa_bloqueada'] ?? null,
                    'boleto' => isset($event['boleto_numero']) ? [
                        'cGerado' => $event['boleto_gerado'] ?? 'N',
                        'cNumBoleto' => $event['boleto_numero'] ?? '',
                        'cNumBancario' => $event['boleto_numero'] ?? ''
                    ] : null,
                ]
            );

            $actionWord = str_contains($topicLower, 'incluido') ? 'incluído' : (str_contains($topicLower, 'alterado') ? 'alterado' : 'boleto gerado');
            $type = str_contains($topicLower, 'incluido') ? 'receivable_created' : 'receivable_updated';

            NotificationService::send(
                $type,
                "Título " . ucfirst($actionWord),
                "O título #{$event['codigo_lancamento_omie']} (Valor: R$ " . number_format($event['valor_documento'], 2, ',', '.') . ") da {$uloSource} foi {$actionWord}."
            );
        }

        elseif (str_contains($topicLower, 'baixarealizada')) {
            $baixas = isset($event[0]) ? $event : [$event];
            foreach ($baixas as $baixa) {
                $titles = $baixa['conta_a_receber'] ?? [];
                foreach ($titles as $title) {
                    $dbTitle = OmieContaReceber::where('ulo_source', $uloSource)
                        ->where('codigo_lancamento_omie', $title['codigo_lancamento_omie'])
                        ->first();
                    if ($dbTitle) {
                        $dbTitle->update(['status_titulo' => 'RECEBIDO']);
                    }

                    NotificationService::send(
                        'receivable_paid',
                        "Título Pago/Liquidado",
                        "O título #{$title['codigo_lancamento_omie']} (Valor: R$ " . number_format($title['valor_documento'], 2, ',', '.') . ") da {$uloSource} foi liquidado/pago."
                    );
                }
            }
        }

        elseif (str_contains($topicLower, 'baixacancelada')) {
            $baixas = isset($event[0]) ? $event : [$event];
            foreach ($baixas as $baixa) {
                $titles = $baixa['conta_a_receber'] ?? [];
                foreach ($titles as $title) {
                    $dbTitle = OmieContaReceber::where('ulo_source', $uloSource)
                        ->where('codigo_lancamento_omie', $title['codigo_lancamento_omie'])
                        ->first();
                    if ($dbTitle) {
                        $status = $this->calculateStatus(null, $dbTitle->data_previsao?->format('Y-m-d'));
                        $dbTitle->update(['status_titulo' => $status]);
                    } else {
                        $status = 'A VENCER';
                    }

                    NotificationService::send(
                        'receivable_updated',
                        "Baixa Cancelada",
                        "A baixa do título #{$title['codigo_lancamento_omie']} da {$uloSource} foi cancelada. O título retornou para status {$status}."
                    );
                }
            }
        }

        elseif (str_contains($topicLower, 'excluido')) {
            $dbTitle = OmieContaReceber::where('ulo_source', $uloSource)
                ->where('codigo_lancamento_omie', $event['codigo_lancamento_omie'])
                ->first();
            if ($dbTitle) {
                $dbTitle->update(['status_titulo' => 'CANCELADO']);
            }

            NotificationService::send(
                'receivable_updated',
                "Título Excluído",
                "O título #{$event['codigo_lancamento_omie']} da {$uloSource} foi excluído/cancelado no Omie."
            );
        }
    }

    /**
     * Lógica de processamento para Clientes.
     */
    private function handleClient($uloSource, $topic, $event)
    {
        $topicLower = strtolower($topic);

        OmieCliente::updateOrCreate(
            [
                'ulo_source' => $uloSource,
                'codigo_cliente_omie' => $event['codigo_cliente_omie']
            ],
            [
                'codigo_cliente_integracao' => $event['codigo_cliente_integracao'] ?? null,
                'cnpj_cpf' => $event['cnpj_cpf'],
                'razao_social' => $event['razao_social'] ?: ($event['nome_fantasia'] ?: ''),
                'nome_fantasia' => $event['nome_fantasia'] ?: ($event['razao_social'] ?: ''),
                'bairro' => $event['bairro'] ?? null,
                'cep' => $event['cep'] ?? null,
                'cidade' => $event['cidade'] ?? null,
                'cidade_ibge' => $event['cidade_ibge'] ?? null,
                'estado' => $event['estado'] ?? null,
                'endereco' => $event['endereco'] ?? null,
                'endereco_numero' => $event['endereco_numero'] ?? null,
                'complemento' => $event['complemento'] ?? null,
                'inativo' => $event['inativo'] ?? 'N',
                'telefone1_ddd' => $event['telefone1_ddd'] ?? ($event['telefone2_ddd'] ?? null),
                'telefone1_numero' => $event['telefone1_numero'] ?? ($event['telefone2_numero'] ?? null),
            ]
        );

        $actionWord = str_contains($topicLower, 'incluido') ? 'cadastrado' : 'atualizado';
        $type = 'client_created';

        NotificationService::send(
            $type,
            "Cliente " . ucfirst($actionWord),
            "O cliente {$event['razao_social']} ({$event['cnpj_cpf']}) da {$uloSource} foi {$actionWord}."
        );
    }
}
