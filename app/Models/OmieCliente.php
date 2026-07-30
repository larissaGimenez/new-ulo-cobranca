<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'codigo_cliente_omie' => 'integer',
        'dadosBancarios' => 'array',
        'recomendacoes' => 'array',
        'tags' => 'array',
        'info' => 'array',
    ];
}
