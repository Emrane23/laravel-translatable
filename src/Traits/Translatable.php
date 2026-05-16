<?php

namespace Emrane23\Translatable\Traits;

use Emrane23\Translatable\Models\Translation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

trait Translatable
{
    /**
     * Check if this model supports translation.
     */
    public function translatable(): bool
    {
        return !empty($this->getTranslatableAttributes());
    }

    /**
     * Get the list of translatable attributes.
     */
    public function getTranslatableAttributes(): array
    {
        return property_exists($this, 'translatable') ? $this->translatable : [];
    }

    /**
     * HasMany relation to translations.
     */
    public function translations()
    {
        return $this->hasMany(Translation::class, 'foreign_key', 'id')
            ->where('table_name', $this->getTable());
    }

    /**
     * Scope to eager load translations for a locale.
     */
    public function scopeWithTranslation(Builder $query, ?string $locale = null, bool|string $fallback = true): void
    {
        $locale   = $locale ?? app()->getLocale();
        $fallback = $fallback === true
            ? config('translatable.fallback_locale', config('app.fallback_locale', 'en'))
            : $fallback;

        $query->with(['translations' => function (Relation $query) use ($locale, $fallback) {
            $query->where(function ($q) use ($locale, $fallback) {
                $q->where('locale', $locale);
                if ($fallback !== false) {
                    $q->orWhere('locale', $fallback);
                }
            });
        }]);
    }

    /**
     * Get a translated attribute value.
     */
    public function getTranslatedAttribute(string $attribute, ?string $locale = null, bool|string $fallback = true): mixed
    {
        [$value] = $this->getTranslatedAttributeMeta($attribute, $locale, $fallback);
        return $value;
    }

    /**
     * Get translated attribute with full metadata [value, locale, found].
     */
    public function getTranslatedAttributeMeta(string $attribute, ?string $locale = null, bool|string $fallback = true): array
    {
        if (!in_array($attribute, $this->getTranslatableAttributes())) {
            return [parent::getAttributeValue($attribute), config('app.locale'), false];
        }

        $locale  = $locale ?? app()->getLocale();
        $default = config('translatable.default_locale', config('app.fallback_locale', 'en'));
        $fallback = $fallback === true
            ? config('translatable.fallback_locale', config('app.fallback_locale', 'en'))
            : $fallback;

        // Default locale → return column value directly (no join needed)
        if ($locale === $default) {
            return [parent::getAttributeValue($attribute), $default, true];
        }

        // Load translations if not already loaded
        if (!$this->relationLoaded('translations')) {
            $this->load('translations');
        }

        $translations = $this->getRelation('translations')
            ->where('column_name', $attribute);

        // Try requested locale
        $translation = $translations->where('locale', $locale)->first();
        if ($translation) {
            return [$translation->value, $locale, true];
        }

        // Try fallback locale
        if ($fallback && $fallback !== $locale && $fallback !== $default) {
            $fallbackTranslation = $translations->where('locale', $fallback)->first();
            if ($fallbackTranslation) {
                return [$fallbackTranslation->value, $fallback, true];
            }
        }

        // Return default column value
        return [parent::getAttributeValue($attribute), $default, false];
    }

    /**
     * Set translations for multiple locales.
     */
    public function setAttributeTranslations(string $attribute, array $translations, bool $save = false): array
    {
        $responses = [];
        $default   = config('translatable.default_locale', config('app.fallback_locale', 'en'));

        foreach ($translations as $locale => $value) {
            if ($locale === $default) {
                $this->setAttribute($attribute, $value);
                if ($save) {
                    $this->save();
                }
                continue;
            }

            $responses[$locale] = $this->translations()->updateOrCreate(
                [
                    'column_name' => $attribute,
                    'locale'      => $locale,
                    'table_name'  => $this->getTable(),
                ],
                ['value' => $value]
            );
        }

        return $responses;
    }

    /**
     * Delete translations for one or more attributes.
     */
    public function deleteAttributeTranslations(array $attributes, array|string|null $locales = null): void
    {
        $this->translations()
            ->whereIn('column_name', $attributes)
            ->when(!is_null($locales), function ($query) use ($locales) {
                $method = is_array($locales) ? 'whereIn' : 'where';
                $query->$method('locale', $locales);
            })
            ->delete();
    }

    /**
     * Delete translations for a single attribute.
     */
    public function deleteAttributeTranslation(string $attribute, array|string|null $locales = null): void
    {
        $this->deleteAttributeTranslations([$attribute], $locales);
    }

    /**
     * Override getAttributeValue.
     * Called internally by Eloquent after getAttribute().
     * No type hint to avoid signature conflicts across Laravel versions.
     */
    public function getAttributeValue($key): mixed
    {
        if ($this->exists && in_array($key, $this->getTranslatableAttributes())) {
            return $this->getTranslatedAttribute($key);
        }

        return parent::getAttributeValue($key);
    }
}