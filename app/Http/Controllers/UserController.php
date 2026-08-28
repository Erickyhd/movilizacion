<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Usuarios/Index', [
            'users' => User::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'rol' => 'required|string|in:ADMIN,OPERADOR,LECTOR',
            'permisos' => 'nullable|array',
        ]);

        $defaultPermisos = [
            'usuarios' => $validated['rol'] === 'ADMIN' ? 'ESCRITURA' : ($validated['permisos']['usuarios'] ?? 'LECTURA'),
            'empresas' => $validated['rol'] === 'ADMIN' ? 'ESCRITURA' : ($validated['permisos']['empresas'] ?? 'ESCRITURA'),
            'trabajadores' => $validated['rol'] === 'ADMIN' ? 'ESCRITURA' : ($validated['permisos']['trabajadores'] ?? 'ESCRITURA'),
            'rutas' => $validated['rol'] === 'ADMIN' ? 'ESCRITURA' : ($validated['permisos']['rutas'] ?? 'ESCRITURA'),
            'flota' => $validated['rol'] === 'ADMIN' ? 'ESCRITURA' : ($validated['permisos']['flota'] ?? 'ESCRITURA'),
            'manifiestos' => $validated['rol'] === 'ADMIN' ? 'ESCRITURA' : ($validated['permisos']['manifiestos'] ?? 'ESCRITURA'),
        ];

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'rol' => $validated['rol'],
            'permisos' => $defaultPermisos,
            'estado' => 1,
        ]);

        return back()->with('success', 'Usuario registrado correctamente.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
            'rol' => 'required|string|in:ADMIN,OPERADOR,LECTOR',
            'permisos' => 'nullable|array',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'rol' => $validated['rol'],
        ];

        if ($validated['rol'] === 'ADMIN') {
            $data['permisos'] = [
                'usuarios' => 'ESCRITURA',
                'empresas' => 'ESCRITURA',
                'trabajadores' => 'ESCRITURA',
                'rutas' => 'ESCRITURA',
                'flota' => 'ESCRITURA',
                'manifiestos' => 'ESCRITURA',
            ];
        } else if (isset($validated['permisos'])) {
            $data['permisos'] = $validated['permisos'];
        }

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return back()->with('success', 'Usuario y permisos actualizados correctamente.');
    }

    public function destroy(User $user)
    {
        $nuevoEstado = $user->estado == 1 ? 0 : 1;
        $user->update(['estado' => $nuevoEstado]);

        $accion = $nuevoEstado == 0 ? 'desactivado' : 'reactivado';
        return back()->with('success', "El usuario fue $accion correctamente.");
    }
}