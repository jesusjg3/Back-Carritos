<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado.'], 401);
        }

        $hasAny = false;
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                $hasAny = true;
                break;
            }
        }

        if (!$hasAny) {
            return response()->json(['error' => 'No tienes permisos para realizar esta acción.'], 403);
        }

        return $next($request);
    }
}
