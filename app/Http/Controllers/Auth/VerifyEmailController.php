<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    /**
     * Verifica el email del usuario usando la URL firmada.
     * No requiere que el usuario esté autenticado — busca al usuario
     * por el {id} de la ruta y valida el hash del email.
     */
    public function __invoke(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Verificar que el hash coincida con el email del usuario
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'Enlace de verificación inválido.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('login')->with('status', 'Tu correo ya fue verificado. Inicia sesión.');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('login')->with('status', 'Correo verificado correctamente. Ya puedes iniciar sesión.');
    }
}
