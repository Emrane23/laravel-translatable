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
    'default_locale' => env('APP_LOCALE', 'fr'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
    'supported_locales' => ['fr', 'en'],
];
```

---

## 🚀 Quick Start

### 1. Add the Trait to your Model

```php
use Emrane23\Translatable\Traits\Translatable;

class Season extends Model
{
    use Translatable;

    // Columns that can be translated
    protected $translatable = ['name', 'description'];
}
```

### 2. That's it. It just works. ✨

```php
// App locale is 'fr' → returns "Hiver 2025" (from column)
// App locale is 'en' → returns "Winter 2025" (from translations table)
// App locale is 'es' → returns "Hiver 2025" (fallback to default)

$season->name;
```

---

## 🌐 Middleware — Auto-detect locale from frontend

Add the middleware to your API group:

```php
// app/Http/Kernel.php
'api' => [
    // ...
    \Emrane23\Translatable\Middleware\TranslationMiddleware::class,
],
```

Now send the locale from your frontend:

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
$season->name;

// Explicit locale
$season->getTranslatedAttribute('name', 'en');

// With fallback control
$season->getTranslatedAttribute('name', 'es', false); // No fallback
```

### Set translations

```php
$season->setAttributeTranslations('name', [
    'fr' => 'Hiver 2025',   // Saved directly to column
    'en' => 'Winter 2025',  // Saved to translations table
]);
```

### Eager load translations (avoid N+1)

```php
Season::withTranslation()->get();
// or with specific locale
Season::withTranslation('en')->get();
```

### Delete translations

```php
$season->deleteAttributeTranslation('name', 'en');
$season->deleteAttributeTranslations(['name', 'description'], ['en', 'es']);
```

---

## 🌱 Seeder Pattern (Bulk Insert)

The recommended way to seed translations — one query for everything:

```php
use Emrane23\Translatable\Helpers\TranslationSeeder;

class SeasonsSeeder extends Seeder
{
    public function run()
    {
        $seasons = [
            ['name' => ['fr' => 'Hiver 2025', 'en' => 'Winter 2025']],
            ['name' => ['fr' => 'Printemps 2025', 'en' => 'Spring 2025']],
        ];

        TranslationSeeder::bulkSeed(Season::class, $seasons, 'name');
        // Default language → saved to column
        // Other languages → bulk inserted into translations table (single query!)
    }
}
```

---

## 🗄️ Database Structure

```
┌─────────────────────────────┐     ┌──────────────────────────────────────┐
│          seasons            │     │            translations               │
├─────────────────────────────┤     ├──────────────────────────────────────┤
│ id          → 1             │────▶│ table_name  → seasons                │
│ name        → "Hiver 2025"  │     │ foreign_key → 1                      │
│ (default FR)│               │     │ column_name → name                   │
│ ...         │               │     │ locale      → en                     │
└─────────────────────────────┘     │ value       → "Winter 2025"          │
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
class Season extends Model
{
    use Translatable;
    protected $translatable = ['name'];
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

### Scope for performance

```php
// Eager load only the locales you need
Season::withTranslation('en', false)->get(); // No fallback
Season::withTranslation()->get();             // Uses app locale + fallback
```

---

## 📋 Requirements

- PHP 8.1+
- Laravel 10.x / 11.x
- Any database supported by Laravel (MySQL, PostgreSQL, SQLite)

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