<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruta extends Model
{
    use HasFactory;

    protected $table = 'rutas';
    protected $guarded = [];

    public function manifiestos(): HasMany
    {
        return $this->hasMany(Manifiesto::class, 'ruta_id');
    }
}