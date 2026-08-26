<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManifiestoDetalle extends Model
{
    use HasFactory;

    protected $table = 'manifiesto_detalles';
    protected $guarded = [];

    public function manifiesto(): BelongsTo
    {
        return $this->belongsTo(Manifiesto::class);
    }

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }
}