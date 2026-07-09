<?php

namespace App\Http\Middleware;

use App;
use Closure;

class Language
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Forzar español para TODOS los usuarios de Celfix, ignorando la preferencia
        // individual guardada en users.language. La traducción del proyecto está más
        // completa en lang/es/ que en lang/en/, y tener parte del staff viendo inglés
        // y parte español causaba inconsistencias y reportes de "no entiendo el sistema".
        // Para revertir: usar el bloque comentado abajo en vez de esta línea.
        App::setLocale('es');

        // Comportamiento original (idioma por usuario):
        // $locale = config('app.locale');
        // if ($request->session()->has('user.language')) {
        //     $locale = $request->session()->get('user.language');
        // }
        // App::setLocale($locale);

        return $next($request);
    }
}
