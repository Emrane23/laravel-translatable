<?php

namespace Emrane23\Translatable\Tests\Feature;

use Emrane23\Translatable\Middleware\TranslationMiddleware;
use Emrane23\Translatable\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\GenericUser;
use PHPUnit\Framework\Attributes\Test;

class TranslationMiddlewareTest extends TestCase
{
    private function runMiddleware(Request $request, string $source): void
    {
        config(['translatable.locale_source'     => $source]);
        config(['translatable.supported_locales' => ['fr', 'en', 'ar', 'es']]);
        config(['translatable.default_locale'    => 'fr']);

        (new TranslationMiddleware())->handle($request, fn() => new Response());
    }

    // =========================================================================
    // 1. Header source (SPA / API)
    // =========================================================================

    #[Test]
    public function it_detects_locale_from_header(): void
    {
        $request = Request::create('/api/products');
        $request->headers->set('X-Locale', 'ar');

        $this->runMiddleware($request, 'header');

        $this->assertEquals('ar', app()->getLocale());
    }

    #[Test]
    public function it_ignores_header_when_source_is_session(): void
    {
        $request = Request::create('/api/products');
        $request->headers->set('X-Locale', 'ar');
        $request->setLaravelSession(session()->driver());

        $this->runMiddleware($request, 'session');

        $this->assertEquals('fr', app()->getLocale());
    }

    // =========================================================================
    // 2. Query source
    // =========================================================================

    #[Test]
    public function it_detects_locale_from_query_parameter(): void
    {
        $request = Request::create('/products?locale=es');

        $this->runMiddleware($request, 'query');

        $this->assertEquals('es', app()->getLocale());
    }

    // =========================================================================
    // 3. Session source (Monolith)
    // =========================================================================

    #[Test]
    public function it_detects_locale_from_session(): void
    {
        $request = Request::create('/products');
        $session = session()->driver();
        $session->put('locale', 'en');
        $request->setLaravelSession($session);

        $this->runMiddleware($request, 'session');

        $this->assertEquals('en', app()->getLocale());
    }

    // =========================================================================
    // 4. Cookie source
    // =========================================================================

    #[Test]
    public function it_detects_locale_from_cookie(): void
    {
        $request = Request::create('/products', 'GET', [], ['locale' => 'ar']);

        $this->runMiddleware($request, 'cookie');

        $this->assertEquals('ar', app()->getLocale());
    }

    // =========================================================================
    // 5. User source
    // =========================================================================

    #[Test]
    public function it_detects_locale_from_user_preferred_locale(): void
    {
        $user = new class(['id' => 1]) extends GenericUser {
            public function preferredLocale(): string
            {
                return 'ar';
            }
        };

        $this->app['auth']->setUser($user);

        $this->runMiddleware(Request::create('/products'), 'user');

        $this->assertEquals('ar', app()->getLocale());
    }

    #[Test]
    public function it_detects_locale_from_user_locale_attribute(): void
    {
        $user = new class(['id' => 1, 'locale' => 'es']) extends GenericUser {};

        $this->app['auth']->setUser($user);

        $this->runMiddleware(Request::create('/products'), 'user');

        $this->assertEquals('es', app()->getLocale());
    }

    #[Test]
    public function it_falls_back_to_default_when_no_user(): void
    {
        $this->runMiddleware(Request::create('/products'), 'user');

        $this->assertEquals('fr', app()->getLocale());
    }

    // =========================================================================
    // 6. Unsupported locale → fallback to default
    // =========================================================================

    #[Test]
    public function it_falls_back_to_default_when_locale_not_supported(): void
    {
        $request = Request::create('/api/products');
        $request->headers->set('X-Locale', 'de'); // not supported

        $this->runMiddleware($request, 'header');

        $this->assertEquals('fr', app()->getLocale());
    }

    // =========================================================================
    // 7. Empty source → fallback to default
    // =========================================================================

    #[Test]
    public function it_falls_back_to_default_when_source_is_empty(): void
    {
        $request = Request::create('/products'); // nothing set

        $this->runMiddleware($request, 'header');

        $this->assertEquals('fr', app()->getLocale());
    }

    // =========================================================================
    // 8. Unknown source → fallback to default
    // =========================================================================

    #[Test]
    public function it_falls_back_to_default_for_unknown_source(): void
    {
        config(['app.debug' => false]);

        $request = Request::create('/products');

        $this->runMiddleware($request, 'unknown_source');

        $this->assertEquals('fr', app()->getLocale());
    }
}
