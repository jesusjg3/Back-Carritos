<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if (!$user->relationLoaded('rol')) {
            $user->load('rol');
        }

        // Si el usuario no tiene rol o no coincide con los roles requeridos, denegar
        if (!$user->rol || !in_array($user->rol->rol_name, $roles)) {
            return response()->json(['message' => 'No tienes permisos de administrador para realizar esta acción.'], 403);
        }

        return $next($request);
    }
}



