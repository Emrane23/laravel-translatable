# 🌍 laravel-translatable

**A simple, elegant and powerful translation package for Laravel — by Emrane Klaai**

[![Latest Version on Packagist](https://img.shields.io/packagist/v/emrane23/laravel-translatable.svg?style=flat-square)](https://packagist.org/packages/emrane23/laravel-translatable)
[![Total Downloads](https://img.shields.io/packagist/dt/emrane23/laravel-translatable.svg?style=flat-square)](https://packagist.org/packages/emrane23/laravel-translatable)
[![License](https://img.shields.io/packagist/l/emrane23/laravel-translatable.svg?style=flat-square)](LICENSE.md)

---

## ✨ Philosophy

Most translation packages store ALL languages in separate columns or JSON fields.  
This package takes a different approach:

- ✅ **Default language** lives directly in the model column (fast, native SQL)
- ✅ **Other languages** live in a separate `translations` table (clean, scalable)
- ✅ **Automatic fallback** — if a translation is missing, returns the default language
- ✅ **Magic getter** — just call `$model->name`, it returns the right language automatically
- ✅ **Zero code change** in controllers or views

---

## 📋 Requirements

- PHP 8.1+
- Laravel 10.x / 11.x / 12.x / 13.x
- Any database supported by Laravel (MySQL, PostgreSQL, SQLite)

---

## 📦 Installation

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

## ⚙️ Configuration

```php
// config/translatable.php
return [
    'default_locale'    => env('APP_LOCALE', 'fr'),
    'fallback_locale'   => env('APP_FALLBACK_LOCALE', 'en'),
    'supported_locales' => ['fr', 'en', 'ar', 'es'],
];
```

And in your `.env`:

```env
APP_LOCALE=fr
APP_FALLBACK_LOCALE=en
```

---

## 🚀 Quick Start

### 1. Add the Trait to your Model

```php
use Emrane23\Translatable\Traits\Translatable;

class Product extends Model
{
    use Translatable;

    protected $fillable = ['name', 'description', 'price'];

    // Columns that can be translated
    protected $translatable = ['name', 'description'];
}
```

### 2. That's it. It just works. ✨

```php
// App locale is 'fr' → returns "Ordinateur portable" (from column)
// App locale is 'en' → returns "Laptop" (from translations table)
// App locale is 'ar' → returns "حاسوب محمول" (from translations table)
// App locale is 'de' → returns "Ordinateur portable" (fallback to default)

$product->name;
```

---

## 🌐 Middleware — Auto-detect locale from frontend

The `TranslationMiddleware` reads the `X-Locale` header from your frontend and sets the application locale automatically.

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
        // ...
        TranslationMiddleware::class,
    ],
];
```

### Frontend usage

```javascript
// Axios — Vue.js / React / any SPA
axios.defaults.headers.common['X-Locale'] = 'en';
```

The middleware reads the `X-Locale` header and sets the application locale automatically.  
**Carbon dates, validation messages, error responses — everything follows.** 🎯

---

## 📧 Email Locale — HasLocalePreference

Add this to your User model to send emails in the user's preferred language:

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

Laravel will automatically use the user's locale when sending notifications. 🚀

---

## 📚 Available Methods

### Get a translated attribute

```php
// Uses current app locale automatically
$product->name;

// Explicit locale
$product->getTranslatedAttribute('name', 'en');

// With fallback control
$product->getTranslatedAttribute('name', 'es', false); // No fallback
```

### Get translation with metadata

```php
// Returns [value, locale_used, found]
[$value, $locale, $found] = $product->getTranslatedAttributeMeta('name', 'en');

// $value  → "Laptop"
// $locale → "en"
// $found  → true
```

### Set translations

```php
$product->setAttributeTranslations('name', [
    'fr' => 'Ordinateur portable',  // Saved directly to column
    'en' => 'Laptop',               // Saved to translations table
    'ar' => 'حاسوب محمول',          // Saved to translations table
    'es' => 'Portátil',             // Saved to translations table
]);

// Save immediately
$product->setAttributeTranslations('name', [
    'en' => 'Laptop',
], save: true);
```

### Eager load translations (avoid N+1)

```php
// Uses current app locale + fallback
Product::withTranslation()->get();

// Specific locale
Product::withTranslation('en')->get();

// Without fallback
Product::withTranslation('en', false)->get();
```

### Delete translations

```php
// Delete one locale for one attribute
$product->deleteAttributeTranslation('name', 'en');

// Delete multiple locales for one attribute
$product->deleteAttributeTranslation('name', ['en', 'es']);

// Delete multiple attributes and locales
$product->deleteAttributeTranslations(['name', 'description'], ['en', 'es']);

// Delete all translations for given attributes
$product->deleteAttributeTranslations(['name', 'description']);
```

### Check translatability

```php
$product->translatable(); // → true

$product->getTranslatableAttributes(); // → ['name', 'description']
```

---

## 🌱 Seeder Pattern (Bulk Insert)

The recommended way to seed translations — one query for everything:

### Method 1 — `bulkSeed` (simplest)

```php
use Emrane23\Translatable\Helpers\TranslationSeeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $p1 = Product::create(['name' => 'Ordinateur portable', 'price' => 999.99]);
        $p2 = Product::create(['name' => 'Souris sans fil',     'price' => 29.99]);

        TranslationSeeder::bulkSeed(Product::class, [
            [
                'id'          => $p1->id,
                'name'        => ['fr' => 'Ordinateur portable', 'en' => 'Laptop',        'ar' => 'حاسوب محمول',  'es' => 'Portátil'],
                'description' => ['fr' => 'Puissant et léger',   'en' => 'Powerful light', 'ar' => 'قوي وخفيف',   'es' => 'Potente y ligero'],
            ],
            [
                'id'          => $p2->id,
                'name'        => ['fr' => 'Souris sans fil', 'en' => 'Wireless Mouse', 'ar' => 'فأرة لاسلكية', 'es' => 'Ratón inalámbrico'],
                'description' => ['fr' => 'Ergonomique',     'en' => 'Ergonomic',      'ar' => 'مريح',          'es' => 'Ergonómico'],
            ],
        ], ['name', 'description']);
    }
}
```

### Method 2 — `prepare` + `flush` (multiple models at once)

```php
use Emrane23\Translatable\Helpers\TranslationSeeder;

$translations = [];

// Prepare products
foreach ($products as $product) {
    $translations = array_merge($translations,
        TranslationSeeder::prepare('products', $product->id, 'name', [
            'en' => 'Laptop',
            'ar' => 'حاسوب محمول',
        ])
    );
}

// Prepare rewards
foreach ($rewards as $reward) {
    $translations = array_merge($translations,
        TranslationSeeder::prepare('rewards', $reward->id, 'name', [
            'en' => 'Gold Trophy',
            'ar' => 'كأس ذهبي',
        ])
    );
}

// Single bulk insert for everything! 🚀
TranslationSeeder::flush($translations);
```

---

## 🗄️ Database Structure

```
┌─────────────────────────────┐     ┌──────────────────────────────────────┐
│          products           │     │            translations               │
├─────────────────────────────┤     ├──────────────────────────────────────┤
│ id          → 1             │────▶│ table_name  → products               │
│ name        → "Ordi..."     │     │ foreign_key → 1                      │
│ (default FR)                │     │ column_name → name                   │
│ ...                         │     │ locale      → en                     │
└─────────────────────────────┘     │ value       → "Laptop"               │
                                    └──────────────────────────────────────┘
```

**Why this structure?**

- Default language queries are native SQL — no joins needed → **maximum performance**
- Other languages are fetched only when needed → **lazy by design**
- One `translations` table for ALL models → **simple schema**
- Automatic fallback chain: `requested locale → fallback locale → default column`

---

## 🔧 Advanced Usage

### Multiple translatable models

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

All share the same `translations` table. Zero extra migrations needed.

---

## 🤝 Contributing

Contributions are welcome! Please read the contributing guide first.

```bash
git clone https://github.com/emrane23/laravel-translatable
cd laravel-translatable
composer install
composer test
```

---

## 📝 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

## 🔒 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## 👨‍💻 Author

**Emrane Klaai**  
- GitHub: [@Emrane23](https://github.com/Emrane23)  
- Built with 💖 from Tunisia 🇹🇳

---

*"The best architecture is the one that solves real problems elegantly."*