<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 1. Buscar si el usuario existe por correo
        $user = User::where('email', $request->email)->first();

        // 2. Si el usuario existe pero ha sido inhabilitado/eliminado (estado == 0)
        if ($user && ($user->estado == 0 || $user->estado === false || $user->estado === '0')) {
            return back()->withErrors([
                'email' => 'Esta cuenta de usuario ha sido inhabilitada o eliminada del sistema. Acceso denegado.',
            ]);
        }

        // 3. Autenticar únicamente si la contraseña coincide Y el estado es activo (1 / true)
        if (Auth::attempt(array_merge($credentials, ['estado' => 1]), $request->boolean('remember')) ||
            Auth::attempt(array_merge($credentials, ['estado' => true]), $request->boolean('remember'))) {
            
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}