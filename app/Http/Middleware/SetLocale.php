<?php

namespace App\Http\Middleware;

use App\Domain\Identity\Actions\UpdateLocale;
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
        $requested = $request->query('lang');

        if ($requested !== null && in_array($requested, ['ar', 'en'], true)) {
            session(['locale' => $requested]);

            if (($user = $request->user()) !== null) {
                app(UpdateLocale::class)->handle($user, $requested);
            }
        }

        $locale = session('locale');

        if ($locale === null) {
            $locale = config('app.locale');
            $viewer = $request->user();

            if ($viewer !== null) {
                $locale = $viewer->locale ?? $locale;
            }
        }

        app()->setLocale(in_array($locale, ['ar', 'en'], true) ? $locale : 'ar');

        return $next($request);
    }
}
