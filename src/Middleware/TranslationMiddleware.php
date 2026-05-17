<?php

namespace Emrane23\Translatable\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TranslationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Detects the current locale from the configured source.
     * Falls back to default_locale if nothing is found or if
     * the detected locale is not in supported_locales.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);

        app()->setLocale($locale);

        return $next($request);
    }

    /**
     * Detect locale from the configured source.
     */
    protected function detectLocale(Request $request): string
    {
        $source    = config('translatable.locale_source', 'header');
        $supported = config('translatable.supported_locales', []);
        $default   = config('translatable.default_locale', config('app.locale', 'en'));

        $locale = $this->resolveSource($request, $source);

        if (!$locale) {
            return $default;
        }

        if (!empty($supported) && !in_array($locale, $supported)) {
            return $default;
        }

        return $locale;
    }

    /**
     * Resolve locale from the configured source.
     */
    protected function resolveSource(Request $request, string $source): ?string
    {
        $valid = ['header', 'query', 'session', 'cookie', 'user'];

        if (!in_array($source, $valid)) {
            if (app()->hasDebugModeEnabled()) {
                throw new \InvalidArgumentException(
                    "Invalid locale_source [{$source}]. Allowed values: " . implode(', ', $valid)
                );
            }

            return null;
        }

        return match ($source) {
            'header'  => $request->header('X-Locale')        ?: null,
            'query'   => $request->query('locale')            ?: null,
            'session' => $request->session()?->get('locale') ?: null,
            'cookie'  => $request->cookie('locale')           ?: null,
            'user'    => $this->resolveUserLocale()           ?: null,
        };
    }

    /**
     * Resolve locale from the authenticated user model.
     *
     * Uses preferredLocale() if available (HasLocalePreference interface).
     * Falls back to a direct locale attribute otherwise.
     *
     * You can customize the locale resolution in your User model:
     *
     * Option 1 — HasLocalePreference (recommended):
     *   use Illuminate\Contracts\Translation\HasLocalePreference;
     *
     *   class User extends Authenticatable implements HasLocalePreference
     *   {
     *       public function preferredLocale(): string
     *       {
     *           return $this->locale ?? config('app.locale');
     *       }
     *   }
     *
     * Option 2 — Custom attribute:
     *   public function getLocaleAttribute(): string
     *   {
     *       return $this->settings['language'] ?? config('app.locale');
     *   }
     */
    protected function resolveUserLocale(): ?string
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return null;
            }

            if (method_exists($user, 'preferredLocale')) {
                return $user->preferredLocale() ?: null;
            }

            return $user->locale ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}
