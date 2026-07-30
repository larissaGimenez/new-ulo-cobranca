<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OmieChangeLog extends Model
{
    // No timestamps since migration has created_at timestamp only (or we can use timestamps = false)
    public $timestamps = false;

    protected $fillable = [
        'ulo_source',
        'entity_type',
        'entity_id',
        'action',
        'details',
        'created_at',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Bootstrap the model and handle the creating event to set created_at.
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_at = $model->created_at ?? now();
        });
    }
}
