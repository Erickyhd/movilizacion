<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manifiesto extends Model
{
    use HasFactory;

    protected $table = 'manifiestos';
    protected $guarded = [];

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class);
    }

    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(Conductor::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(ManifiestoDetalle::class, 'manifiesto_id');
    }
}