<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($requested = $request->query('lang')) {
            if (in_array($requested, ['ar', 'en'], true)) {
                session(['locale' => $requested]);
            }
        }

        $locale = session('locale', config('app.locale'));

        app()->setLocale(in_array($locale, ['ar', 'en'], true) ? $locale : 'ar');

        return $next($request);
    }
}
