<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Único registro inicial: Usuario Administrador para Inicio de Sesión
        User::create([
            'name' => 'Administrador Magori',
            'email' => 'admin@movilizacion.local',
            'password' => Hash::make('admin1234'),
            'rol' => 'ADMIN',
            'estado' => true,
        ]);
    }
}