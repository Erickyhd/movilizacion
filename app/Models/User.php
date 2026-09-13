<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'permisos',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permisos' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return strtoupper($this->rol ?? '') === 'ADMIN';
    }

    public function isLector(): bool
    {
        return strtoupper($this->rol ?? '') === 'LECTOR';
    }

    public function isOperador(): bool
    {
        return strtoupper($this->rol ?? '') === 'OPERADOR';
    }

    public function hasWritePermission(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isLector()) {
            return false;
        }

        if ($module === 'usuarios') {
            return false;
        }

        $permisos = is_array($this->permisos) ? $this->permisos : json_decode($this->permisos ?? '[]', true);
        return isset($permisos[$module]) && strtoupper($permisos[$module]) === 'ESCRITURA';
    }
}
