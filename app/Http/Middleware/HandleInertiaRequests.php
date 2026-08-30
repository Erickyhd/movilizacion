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

        $defaultPermisos = [
            'usuarios' => 'ESCRITURA',
            'empresas' => 'ESCRITURA',
            'trabajadores' => 'ESCRITURA',
            'rutas' => 'ESCRITURA',
            'flota' => 'ESCRITURA',
            'manifiestos' => 'ESCRITURA',
        ];

        $userPermisos = null;
        if ($user) {
            if ($user->rol === 'ADMIN' || empty($user->permisos)) {
                $userPermisos = $defaultPermisos;
            } else {
                $userPermisos = array_merge($defaultPermisos, is_array($user->permisos) ? $user->permisos : json_decode($user->permisos, true) ?? []);
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'       => $user->id,
                    'name'     => $user->name,
                    'email'    => $user->email,
                    'rol'      => $user->rol ?? 'ADMIN',
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