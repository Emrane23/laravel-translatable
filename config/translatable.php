<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The default locale is stored directly in the model column.
    | This ensures maximum performance for the primary language
    | without any joins or extra queries.
    |
    */
    'default_locale' => env('APP_LOCALE', 'fr'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    |
    | When a translation is not found for the requested locale,
    | the package will try this fallback locale before returning
    | the default column value.
    |
    */
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | List of locales accepted by the TranslationMiddleware.
    | Requests with a locale outside this list are ignored
    | and fall back to the default locale.
    |
    */
    'supported_locales' => ['fr', 'en'],

    /*
    |--------------------------------------------------------------------------
    | Locale Source
    |--------------------------------------------------------------------------
    |
    | Define how the middleware detects the current locale.
    | Pick the one that matches your project architecture.
    |
    | Available values:
    |   'header'  → X-Locale request header          (SPA / API / mobile)
    |   'query'   → ?locale= URL parameter            (direct URLs, emails)
    |   'session' → session()->get('locale')          (classic monolith)
    |   'cookie'  → cookie('locale')                  (persistent preference)
    |   'user'    → auth()->user()->preferredLocale() (per-user preference)
    |              or auth()->user()->locale if preferredLocale() not defined
    |
    | An InvalidArgumentException is thrown if an invalid value is provided.
    |
    */
    'locale_source' => 'header',

];