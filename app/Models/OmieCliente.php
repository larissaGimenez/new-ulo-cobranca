<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OmieCliente extends Model
{
    protected $table = 'omie_clientes';

    protected $fillable = [
        'ulo_source',
        'codigo_cliente_omie',
        'codigo_cliente_integracao',
        'cnpj_cpf',
        'razao_social',
        'nome_fantasia',
        'bairro',
        'cep',
        'cidade',
        'cidade_ibge',
        'estado',
        'endereco',
        'endereco_numero',
        'complemento',
        'email',
        'telefone1_ddd',
        'telefone1_numero',
        'inativo',
        'pessoa_fisica',
        'dadosBancarios',
        'recomendacoes',
        'tags',
        'info',
        'divida_comum_total',
        'divida_redecard_total',
        'qtd_titulos_comum',
        'qtd_titulos_redecard',
        'dias_atraso_maximo',
        'status_cobranca',
    ];

    protected $casts = [
        'codigo_cliente_omie' => 'integer',
        'dadosBancarios' => 'array',
        'recomendacoes' => 'array',
        'tags' => 'array',
        'info' => 'array',
        'divida_comum_total' => 'float',
        'divida_redecard_total' => 'float',
        'qtd_titulos_comum' => 'integer',
        'qtd_titulos_redecard' => 'integer',
        'dias_atraso_maximo' => 'integer',
    ];

    public function contasReceber()
    {
        return $this->hasMany(OmieContaReceber::class, 'codigo_cliente_fornecedor', 'codigo_cliente_omie')
            ->where('ulo_source', $this->ulo_source);
    }

    /**
     * Recalcula fisicamente o saldo desnormalizado de um único cliente por CNPJ/CPF (Instantâneo)
     */
    public static function recalculateFinancialsForClient($cnpjCpf)
    {
        if (empty($cnpjCpf)) return;

        $redecardCcKeys = DB::table('omie_contas_correntes')
            ->where(function($q) {
                $q->where('codigo_banco', '971')->orWhere('descricao', 'ILIKE', '%Redecard%');
            })
            ->select('n_cod_c_c', 'ulo_source')
            ->get()
            ->map(fn($item) => $item->ulo_source . '_' . $item->n_cod_c_c)
            ->toArray();

        $receivables = DB::table('omie_contas_receber as cp')
            ->join('omie_clientes as c', function($join) {
                $join->on('cp.codigo_cliente_fornecedor', '=', 'c.codigo_cliente_omie')
                     ->on('cp.ulo_source', '=', 'c.ulo_source');
            })
            ->where('c.cnpj_cpf', $cnpjCpf)
            ->where('cp.status_titulo', 'ATRASADO')
            ->select('cp.valor_documento', 'cp.data_previsao', 'cp.id_conta_corrente', 'cp.ulo_source')
            ->get();

        $dividaComum = 0.0;
        $dividaRedecard = 0.0;
        $qtdComum = 0;
        $qtdRedecard = 0;
        $diasAtrasoMax = 0;

        $today = \Carbon\Carbon::today();

        foreach ($receivables as $r) {
            $key = $r->ulo_source . '_' . $r->id_conta_corrente;
            $isRedecard = in_array($key, $redecardCcKeys);

            if ($isRedecard) {
                $dividaRedecard += (float)$r->valor_documento;
                $qtdRedecard++;
            } else {
                $dividaComum += (float)$r->valor_documento;
                $qtdComum++;
            }

            if ($r->data_previsao) {
                $prev = \Carbon\Carbon::parse($r->data_previsao);
                $diff = $today->diffInDays($prev, false);
                if ($diff < 0) {
                    $dias = abs((int)$diff);
                    if ($dias > $diasAtrasoMax) {
                        $diasAtrasoMax = $dias;
                    }
                }
            }
        }

        $statusCobranca = 'adimplente';
        if ($dividaComum > 0) {
            $statusCobranca = 'inadimplente';
        } elseif ($dividaRedecard > 0) {
            $statusCobranca = 'inadimplente_redecard';
        }

        static::where('cnpj_cpf', $cnpjCpf)->update([
            'divida_comum_total' => $dividaComum,
            'divida_redecard_total' => $dividaRedecard,
            'qtd_titulos_comum' => $qtdComum,
            'qtd_titulos_redecard' => $qtdRedecard,
            'dias_atraso_maximo' => $diasAtrasoMax,
            'status_cobranca' => $statusCobranca,
        ]);
    }

    /**
     * Recalcula fisicamente os saldos desnormalizados de TODOS os clientes em lote (Bulk Ops)
     */
    public static function recalculateAllClientsFinancials()
    {
        DB::statement("
            UPDATE omie_clientes 
            SET divida_comum_total = 0,
                divida_redecard_total = 0,
                qtd_titulos_comum = 0,
                qtd_titulos_redecard = 0,
                dias_atraso_maximo = 0,
                status_cobranca = 'adimplente'
        ");

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
    }
}
