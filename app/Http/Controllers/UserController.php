<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
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
                'rol.required' => 'Debe seleccionar un rol de usuario válido.',
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
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
                'password' => Hash::make($validated['password']),
                'rol' => $validated['rol'],
                'permisos' => $defaultPermisos,
                'estado' => 1,
            ]);

            return back()->with('success', 'Usuario registrado correctamente.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al registrar el usuario: ' . $e->getMessage())->withInput();
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'password' => 'nullable|min:6',
                'rol' => 'required|string|in:ADMIN,OPERADOR,LECTOR',
                'permisos' => 'nullable|array',
            ], [
                'name.required' => 'El nombre completo del usuario es obligatorio.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'email.email' => 'Ingrese una dirección de correo válida.',
                'email.unique' => 'El correo electrónico ingresado ya se encuentra registrado.',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
                'rol.required' => 'Debe seleccionar un rol de usuario válido.',
            ]);

            $data = [
                'name' => trim($validated['name']),
                'email' => strtolower(trim($validated['email'])),
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
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al actualizar el usuario: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(User $user)
    {
        try {
            if (auth()->id() === $user->id) {
                return back()->with('error', 'No puedes deshabilitar tu propio usuario administrador en sesión activa.')
                    ->withErrors(['email' => 'No puedes deshabilitar tu propio usuario administrador en sesión activa.']);
            }

            $nuevoEstado = ($user->estado == 1 || $user->estado === true) ? 0 : 1;
            $user->update(['estado' => $nuevoEstado]);

            $accion = $nuevoEstado == 0 ? 'desactivado / inhabilitado' : 'reactivado';
            return back()->with('success', "El usuario fue $accion correctamente.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al cambiar estado del usuario: ' . $e->getMessage());
        }
    }
}