<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Window;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    public function boot(): void
    {
        try {
            if (Schema::hasTable('users') && User::count() === 0) {
                User::create([
                    'name' => 'Administrador Magori',
                    'email' => 'admin@movilizacion.local',
                    'password' => Hash::make('admin1234'),
                    'rol' => 'ADMIN',
                    'estado' => true,
                ]);
            }
        } catch (\Throwable $e) {
        }

        Window::open()
            ->title('Sistema de Control y Gestión de Movilización HSEQ')
            ->width(1366)
            ->height(850)
            ->minWidth(1024)
            ->minHeight(700)
            ->rememberState();
    }

    public function phpIni(): array
    {
        return [];
    }
}