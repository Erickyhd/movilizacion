<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conductor extends Model
{
    use HasFactory;

    protected $table = 'conductores';
    protected $guarded = [];

    protected $appends = ['nombre_completo', 'apellidos'];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function getApellidosAttribute(): string
    {
        if ($this->apellido_paterno || $this->apellido_materno) {
            return trim("{$this->apellido_paterno} {$this->apellido_materno}");
        }
        return $this->trabajador ? $this->trabajador->apellidos : '';
    }

    public function getNombreCompletoAttribute(): string
    {
        if ($this->nombres) {
            return trim("{$this->nombres} {$this->apellidos}");
        }
        return $this->trabajador ? $this->trabajador->nombre_completo : '';
    }
}