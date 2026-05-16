<?php

namespace Emrane23\Translatable\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class TranslationMiddleware
{
    /**
     * Intercepts X-Locale header and sets the application locale.
     * Works with Carbon dates, validation messages, and all Laravel translations.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale    = $request->header('X-Locale');
        $supported = config('translatable.supported_locales', ['fr', 'en']);

        if ($locale && in_array($locale, $supported)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}