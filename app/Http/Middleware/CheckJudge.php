<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckJudge
{
    /**
     * Handle an incoming request.
     *
     * Bloqueia o acesso ao painel do julgador para usuários sem `is_judge = true`:
     * faz logout, redireciona ao login e exibe "Perfil sem Acesso!" (spec 0002 / NM-03).
     * Usuários não autenticados são tratados pelo middleware `auth` (externo).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isJudge()) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('error', 'Perfil sem Acesso!');
    }
}
