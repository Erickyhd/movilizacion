<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
        ], [
            'name.required' => 'El nombre completo del usuario es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese una dirección de correo válida.',
            'email.unique' => 'El correo electrónico ingresado ya se encuentra registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
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
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|min:6',
            'rol' => 'required|string|in:ADMIN,OPERADOR,LECTOR',
            'permisos' => 'nullable|array',
        ], [
            'name.required' => 'El nombre completo del usuario es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese una dirección de correo válida.',
            'email.unique' => 'El correo electrónico ingresado ya se encuentra registrado.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
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
        if (auth()->id() === $user->id) {
            return back()->withErrors(['email' => 'No puedes deshabilitar tu propio usuario administrador en sesión activa.']);
        }

        $nuevoEstado = ($user->estado == 1 || $user->estado === true) ? 0 : 1;
        $user->update(['estado' => $nuevoEstado]);

        $accion = $nuevoEstado == 0 ? 'desactivado / inhabilitado' : 'reactivado';
        return back()->with('success', "El usuario fue $accion correctamente.");
    }
}