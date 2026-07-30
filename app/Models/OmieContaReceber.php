<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OmieContaReceber extends Model
{
    protected $table = 'omie_contas_receber';

    protected $fillable = [
        'ulo_source',
        'codigo_lancamento_omie',
        'codigo_cliente_fornecedor',
        'codigo_categoria',
        'codigo_tipo_documento',
        'data_emissao',
        'data_vencimento',
        'data_previsao',
        'data_registro',
        'id_conta_corrente',
        'id_origem',
        'numero_parcela',
        'status_titulo',
        'valor_documento',
        'bloqueado',
        'bloquear_baixa',
        'boleto',
        'categorias',
        'distribuicao',
        'info',
    ];

    protected $casts = [
        'codigo_lancamento_omie' => 'integer',
        'codigo_cliente_fornecedor' => 'integer',
        'id_conta_corrente' => 'integer',
        'data_emissao' => 'date',
        'data_vencimento' => 'date',
        'data_previsao' => 'date',
        'data_registro' => 'date',
        'valor_documento' => 'decimal:2',
        'boleto' => 'array',
        'categorias' => 'array',
        'distribuicao' => 'array',
        'info' => 'array',
    ];

    public function cliente()
    {
        return $this->belongsTo(OmieCliente::class, 'codigo_cliente_fornecedor', 'codigo_cliente_omie')
            ->where('ulo_source', $this->ulo_source);
    }
}
