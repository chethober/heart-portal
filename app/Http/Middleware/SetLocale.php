<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use Symfony\Component\HttpFoundation\Response;

/**
 * Use the language the visitor picked (kept in a cookie so it survives
 * logging out), falling back to their browser's preferred language.
 */
class SetLocale
{
    public const COOKIE = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('app.available_locales'));
        $chosen = $request->cookie(self::COOKIE);

        $locale = in_array($chosen, $available, true)
            ? $chosen
            : ($request->getPreferredLanguage($available) ?? config('app.locale'));

        App::setLocale($locale);
        Date::setLocale($locale);

        return $next($request);
    }
}
