<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role = 'admin'): Response
    {

         if (!$request->user()->hasRole($role)) {
            abort(403, 'Acesso negado. Você não tem permissão para acessar esta página.');
        }
        return $next($request);
    }
}
