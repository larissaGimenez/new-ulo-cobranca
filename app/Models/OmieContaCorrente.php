<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OmieContaCorrente extends Model
{
    protected $table = 'omie_contas_correntes';

    protected $fillable = [
        'ulo_source',
        'n_cod_c_c',
        'descricao',
        'codigo_banco',
        'tipo',
        'codigo_agencia',
        'conta_corrente',
        'c_cod_c_c_int',
        'c_sincr_analitica',
    ];

    protected $casts = [
        'n_cod_c_c' => 'integer',
    ];
}
