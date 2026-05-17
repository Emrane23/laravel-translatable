# laravel-translatable

**Zero-config translations for Laravel — SPA, monolith, and everything in between.**

[![Latest Version on Packagist](https://img.shields.io/packagist/v/emrane23/laravel-translatable.svg?style=flat-square)](https://packagist.org/packages/emrane23/laravel-translatable)
[![Total Downloads](https://img.shields.io/packagist/dt/emrane23/laravel-translatable.svg?style=flat-square)](https://packagist.org/packages/emrane23/laravel-translatable)
[![License](https://img.shields.io/packagist/l/emrane23/laravel-translatable.svg?style=flat-square)](LICENSE.md)

---

## Philosophy

Most translation packages store all languages in separate columns or JSON fields.
This package takes a different approach:

- The **default language** lives directly in the model column — fast, native SQL, no joins
- **Other languages** live in a separate `translations` table — clean and scalable
- **Automatic fallback** — if a translation is missing, returns the default language value
- **Magic getter** — just call `$model->name`, it returns the right language automatically
- **Zero code change** in your controllers or views

---

## Hybrid Laravel Support

This package works seamlessly across all Laravel architectures:

- SPA applications (Vue.js / React / Inertia.js)
- Classic Laravel monoliths (Blade + sessions)
- API-first architectures (mobile apps)
- Hybrid systems (mixed environments)

The middleware locale source is fully configurable — you pick the detection mechanism that fits your project.

---

## Requirements

- PHP 8.1+
- Laravel 10.x / 11.x / 12.x / 13.x
- Any database supported by Laravel (MySQL, PostgreSQL, SQLite)

---

## Installation

```bash
composer require emrane23/laravel-translatable
```

Publish and run the migration:

```bash
php artisan vendor:publish --tag="translatable-migrations"
php artisan migrate
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag="translatable-config"
```

---

## Configuration

```php
// config/translatable.php
return [
    'default_locale'    => env('APP_LOCALE', 'fr'),
    'fallback_locale'   => env('APP_FALLBACK_LOCALE', 'en'),
    'supported_locales' => ['fr', 'en', 'ar', 'es'],

    // Pick the one that matches your project — see Middleware section below
    'locale_source' => 'header',
];
```

```env
APP_LOCALE=fr
APP_FALLBACK_LOCALE=en
```

---

## Quick Start

### 1. Add the trait to your model

```php
use Emrane23\Translatable\Traits\Translatable;

class Product extends Model
{
    use Translatable;

    protected $fillable = ['name', 'description', 'price'];

    protected $translatable = ['name', 'description'];
}
```

### 2. That's it.

```php
// App locale is 'fr' → returns "Ordinateur portable" (from column, no join)
// App locale is 'en' → returns "Laptop" (from translations table)
// App locale is 'ar' → returns "حاسوب محمول" (from translations table)
// App locale is 'de' → returns "Ordinateur portable" (fallback to default)

$product->name;
```

No controller changes. No view changes. It just works.

---

## Middleware — Locale Detection

The `TranslationMiddleware` detects the current locale automatically from the source you configure.
An `InvalidArgumentException` is thrown if an invalid source value is provided.

### Laravel 11, 12, 13 — `bootstrap/app.php`

```php
use Emrane23\Translatable\Middleware\TranslationMiddleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->appendToGroup('api', TranslationMiddleware::class);
})
```

### Laravel 10 — `app/Http/Kernel.php`

```php
use Emrane23\Translatable\Middleware\TranslationMiddleware;

protected $middlewareGroups = [
    'api' => [
        TranslationMiddleware::class,
    ],
];
```

### Available sources

| Value | How it works | Best for |
|---|---|---|
| `header` | Reads `X-Locale` request header | SPA, API, mobile |
| `query` | Reads `?locale=` URL parameter | Direct URLs, emails |
| `session` | Reads `session()->get('locale')` | Classic monolith |
| `cookie` | Reads `cookie('locale')` | Persistent preference |
| `user` | Reads from authenticated user | Per-user preference |

### Configure for your architecture

```php
// config/translatable.php

'locale_source' => 'header',   // SPA / API
'locale_source' => 'session',  // Classic monolith
'locale_source' => 'cookie',   // Persistent preference
'locale_source' => 'query',    // URL parameter
'locale_source' => 'user',     // Per-user preference
```

### Frontend (Vue.js / React / any SPA)

```javascript
axios.defaults.headers.common['X-Locale'] = 'ar';
```

### User locale — `preferredLocale()`

The `user` source uses `preferredLocale()` if available (Laravel's `HasLocalePreference` interface),
then falls back to a direct `locale` attribute. You can adapt it to your own mechanism:

```php
// Option 1 — HasLocalePreference (recommended)
use Illuminate\Contracts\Translation\HasLocalePreference;

class User extends Authenticatable implements HasLocalePreference
{
    public function preferredLocale(): string
    {
        return $this->locale ?? config('app.locale');
    }
}

// Option 2 — Custom attribute (adapt to your own mechanism)
public function getLocaleAttribute(): string
{
    return $this->settings['language'] ?? config('app.locale');
}
```

---

## Email Locale

Implement `HasLocalePreference` on your `User` model to send emails in each user's preferred language:

```php
use Illuminate\Contracts\Translation\HasLocalePreference;

class User extends Authenticatable implements HasLocalePreference
{
    public function preferredLocale(): string
    {
        return $this->locale ?? config('app.locale');
    }
}
```

Laravel will automatically use the user's locale when sending notifications.

---

## Available Methods

### Reading translations

```php
// Current app locale (automatic)
$product->name;

// Explicit locale
$product->getTranslatedAttribute('name', 'en');

// Without fallback
$product->getTranslatedAttribute('name', 'es', false);

// With full metadata — returns [value, locale_used, found]
[$value, $locale, $found] = $product->getTranslatedAttributeMeta('name', 'en');
```

### Writing translations

```php
$product->setAttributeTranslations('name', [
    'fr' => 'Ordinateur portable', // saved to column directly
    'en' => 'Laptop',              // saved to translations table
    'ar' => 'حاسوب محمول',
    'es' => 'Portátil',
]);

// Save immediately
$product->setAttributeTranslations('name', ['en' => 'Laptop'], save: true);
```

### Eager loading (avoid N+1)

```php
Product::withTranslation()->get();            // current locale + fallback
Product::withTranslation('en')->get();        // specific locale
Product::withTranslation('en', false)->get(); // no fallback
```

For large applications with heavy traffic, eager load translations globally:

```php
// In your model
protected $with = ['translations'];
```

### Deleting translations

```php
$product->deleteAttributeTranslation('name', 'en');
$product->deleteAttributeTranslation('name', ['en', 'es']);
$product->deleteAttributeTranslations(['name', 'description'], ['en', 'es']);
$product->deleteAttributeTranslations(['name', 'description']); // all locales
```

### Introspection

```php
$product->translatable();               // true
$product->getTranslatableAttributes();  // ['name', 'description']
```

---

## Seeder Pattern

### Method 1 — `bulkSeed`

The simplest way. One bulk query for all translations.

```php
use Emrane23\Translatable\Helpers\TranslationSeeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $p1 = Product::create(['name' => 'Ordinateur portable', 'price' => 999.99]);
        $p2 = Product::create(['name' => 'Souris sans fil', 'price' => 29.99]);

        TranslationSeeder::bulkSeed(Product::class, [
            [
                'id'          => $p1->id,
                'name'        => ['fr' => 'Ordinateur portable', 'en' => 'Laptop', 'ar' => 'حاسوب محمول', 'es' => 'Portátil'],
                'description' => ['fr' => 'Puissant et léger', 'en' => 'Powerful & light', 'ar' => 'قوي وخفيف', 'es' => 'Potente y ligero'],
            ],
            [
                'id'          => $p2->id,
                'name'        => ['fr' => 'Souris sans fil', 'en' => 'Wireless Mouse', 'ar' => 'فأرة لاسلكية', 'es' => 'Ratón inalámbrico'],
                'description' => ['fr' => 'Ergonomique', 'en' => 'Ergonomic', 'ar' => 'مريح', 'es' => 'Ergonómico'],
            ],
        ], ['name', 'description']);
    }
}
```

### Method 2 — `prepare` + `flush`

Useful when seeding multiple models in one shot.

```php
$translations = [];

foreach ($products as $product) {
    $translations = array_merge($translations,
        TranslationSeeder::prepare('products', $product->id, 'name', [
            'en' => 'Laptop',
            'ar' => 'حاسوب محمول',
        ])
    );
}

foreach ($rewards as $reward) {
    $translations = array_merge($translations,
        TranslationSeeder::prepare('rewards', $reward->id, 'name', [
            'en' => 'Gold Trophy',
            'ar' => 'كأس ذهبي',
        ])
    );
}

TranslationSeeder::flush($translations); // single query for everything
```

---

## Database Structure

```
┌──────────────────────┐     ┌──────────────────────────────────────┐
│       products       │     │           translations                │
├──────────────────────┤     ├──────────────────────────────────────┤
│ id       → 1         │────▶│ table_name  → products               │
│ name     → "Ordi..."  │     │ foreign_key → 1                      │
│ (default locale)     │     │ column_name → name                   │
└──────────────────────┘     │ locale      → en                     │
                             │ value       → "Laptop"               │
                             └──────────────────────────────────────┘
```

The default language is stored directly in the model column — no joins needed for the most common case.
Other languages are fetched only when requested. One `translations` table serves all your models
with no extra migrations needed.

Fallback chain: `requested locale → fallback locale → default column`

---

## Advanced Usage

All models share the same `translations` table:

```php
class Product extends Model
{
    use Translatable;
    protected $translatable = ['name', 'description'];
}

class Reward extends Model
{
    use Translatable;
    protected $translatable = ['name', 'description'];
}

class SeasonChallenge extends Model
{
    use Translatable;
    protected $translatable = ['title', 'description'];
}
```

---

## Contributing

```bash
git clone https://github.com/Emrane23/laravel-translatable
cd laravel-translatable
composer install
composer test
```

---

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

---

## License

MIT. See [LICENSE](LICENSE.md).

---

## Author

**Emrane Klaai** — [@Emrane23](https://github.com/Emrane23) — Built from Tunisia

*"The best architecture is the one that solves real problems elegantly."*