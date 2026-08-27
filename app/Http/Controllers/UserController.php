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
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'estado' => 1,
        ]);

        return back()->with('success', 'Usuario registrado correctamente con Estado = 1.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        // Borrado lógico de auditoría: cambiar de estado 1 a estado 0 (o viceversa)
        $nuevoEstado = $user->estado == 1 ? 0 : 1;
        $user->update(['estado' => $nuevoEstado]);

        $accion = $nuevoEstado == 0 ? 'desactivado (Estado = 0)' : 'reactivado (Estado = 1)';
        return back()->with('success', "El usuario fue $accion correctamente.");
    }
}