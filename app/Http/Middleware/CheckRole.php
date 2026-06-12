<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que verifica el rol del usuario autenticado.
 * Si el usuario no tiene el rol requerido, regresa un 403 Forbidden.
 * 
 * Uso en rutas: ->middleware('role:admin')
 */
class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Si no hay usuario autenticado, rechazar
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Verificar si el usuario tiene alguno de los roles permitidos
        if (!in_array($user->role, $roles)) {
            return response()->json(['message' => 'No tienes permiso para acceder a este recurso'], 403);
        }

        return $next($request);
    }
}
