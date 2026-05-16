# Changelog

All notable changes to `laravel-translatable` will be documented in this file.

## [1.0.0] - 2026

### Added
- `Translatable` trait with magic getter and automatic fallback
- `TranslationMiddleware` — reads `X-Locale` header and sets app locale
- `TranslationSeeder` helper — bulk insert translations in a single query
- `Translation` Eloquent model
- `withTranslation()` scope for eager loading (avoids N+1)
- `getTranslatedAttribute()` and `getTranslatedAttributeMeta()` methods
- `setAttributeTranslations()` for updating translations
- `deleteAttributeTranslation()` and `deleteAttributeTranslations()` methods
- Full support for Laravel 10 and 11
- Config file with `default_locale`, `fallback_locale`, `supported_locales`
- Migration for the shared `translations` table with optimized indexes