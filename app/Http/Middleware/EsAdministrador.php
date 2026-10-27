<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EsAdministrador
{
    /**
     * Quien ha entrado con una cuenta que no administra va a la entrada de la administración,
     * que le pide entrar con otra cuenta.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->esAdministrador()) {
            return redirect()->guest(route('admin.login'));
        }

        return $next($request);
    }
}
