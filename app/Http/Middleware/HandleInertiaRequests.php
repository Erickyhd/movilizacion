<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        
        // Si el usuario autenticado está inhabilitado/eliminado (estado == 0), cerrar sesión de inmediato
        if ($user && ($user->estado == 0 || $user->estado === false || $user->estado === '0')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            $user = null;
        }

        $userPermisos = null;
        if ($user) {
            $rol = strtoupper($user->rol ?? 'LECTOR');
            if ($rol === 'ADMIN') {
                $userPermisos = [
                    'usuarios' => 'ESCRITURA',
                    'empresas' => 'ESCRITURA',
                    'trabajadores' => 'ESCRITURA',
                    'rutas' => 'ESCRITURA',
                    'flota' => 'ESCRITURA',
                    'manifiestos' => 'ESCRITURA',
                ];
            } elseif ($rol === 'LECTOR') {
                $userPermisos = [
                    'usuarios' => 'LECTURA',
                    'empresas' => 'LECTURA',
                    'trabajadores' => 'LECTURA',
                    'rutas' => 'LECTURA',
                    'flota' => 'LECTURA',
                    'manifiestos' => 'LECTURA',
                ];
            } else { // OPERADOR
                $parsed = is_array($user->permisos) ? $user->permisos : (json_decode($user->permisos ?? '[]', true) ?? []);
                $userPermisos = [
                    'usuarios' => 'LECTURA',
                    'empresas' => $parsed['empresas'] ?? 'LECTURA',
                    'trabajadores' => $parsed['trabajadores'] ?? 'LECTURA',
                    'rutas' => $parsed['rutas'] ?? 'LECTURA',
                    'flota' => $parsed['flota'] ?? 'LECTURA',
                    'manifiestos' => $parsed['manifiestos'] ?? 'LECTURA',
                ];
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'rol'      => $user->rol ?? 'LECTOR',
                    'permisos' => $userPermisos,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
