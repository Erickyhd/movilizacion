<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trabajador extends Model
{
    use HasFactory;

    protected $table = 'trabajadores';
    protected $guarded = [];
    protected $appends = ['apellidos', 'nombre_completo'];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(DocumentoTrabajador::class, 'trabajador_id');
    }

    public function getApellidosAttribute(): string
    {
        $p = $this->attributes['apellido_paterno'] ?? '';
        $m = $this->attributes['apellido_materno'] ?? '';
        $combined = trim("$p $m");
        return $combined !== '' ? $combined : ($this->attributes['apellidos'] ?? '');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }
}