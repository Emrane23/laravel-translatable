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
    | Requests with X-Locale headers outside this list are ignored.
    |
    */
    'supported_locales' => ['fr', 'en'],
];