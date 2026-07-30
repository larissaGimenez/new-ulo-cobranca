<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OmieContaPagar extends Model
{
    protected $table = 'omie_contas_pagar';

    protected $fillable = [
        'ulo_source',
        'codigo_lancamento_omie',
        'codigo_cliente_fornecedor',
        'codigo_categoria',
        'codigo_tipo_documento',
        'data_emissao',
        'data_entrada',
        'data_previsao',
        'data_vencimento',
        'id_conta_corrente',
        'id_origem',
        'numero_documento',
        'numero_documento_fiscal',
        'numero_parcela',
        'status_titulo',
        'valor_documento',
        'chave_nfe',
        'operacao',
        'baixa_bloqueada',
        'bloqueado',
        'codigo_barras_ficha_compensacao',
        'retem_cofins',
        'retem_csll',
        'retem_inss',
        'retem_ir',
        'retem_iss',
        'retem_pis',
        'info',
        'categorias',
        'cnab_integracao_bancaria',
        'distribuicao',
    ];

    protected $casts = [
        'codigo_lancamento_omie' => 'integer',
        'codigo_cliente_fornecedor' => 'integer',
        'id_conta_corrente' => 'integer',
        'data_emissao' => 'date',
        'data_entrada' => 'date',
        'data_previsao' => 'date',
        'data_vencimento' => 'date',
        'valor_documento' => 'decimal:2',
        'info' => 'array',
        'categorias' => 'array',
        'cnab_integracao_bancaria' => 'array',
        'distribuicao' => 'array',
    ];

    public function cliente()
    {
        return $this->belongsTo(OmieCliente::class, 'codigo_cliente_fornecedor', 'codigo_cliente_omie')
            ->where('ulo_source', $this->ulo_source);
    }
}
