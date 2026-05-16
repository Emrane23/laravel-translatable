<?php

namespace Emrane23\Translatable\Helpers;

use Emrane23\Translatable\Models\Translation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TranslationSeeder
{
    /**
     * Bulk seed translations for a model.
     *
     * Saves the default locale directly to the model column,
     * and inserts all other locales into the translations table
     * in a single bulk query — maximum performance.
     *
     * @param string $modelClass  The Eloquent model class (e.g. Season::class)
     * @param array  $records     Array of records with translatable attributes
     * @param array  $attributes  The attributes to seed translations for
     *
     * Example:
     * TranslationSeeder::bulkSeed(Season::class, [
     *     ['id' => 1, 'name' => ['fr' => 'Hiver 2025', 'en' => 'Winter 2025']],
     *     ['id' => 2, 'name' => ['fr' => 'Printemps 2025', 'en' => 'Spring 2025']],
     * ], ['name']);
     */
    public static function bulkSeed(string $modelClass, array $records, array $attributes): void
    {
        /** @var Model $instance */
        $instance  = new $modelClass;
        $table     = $instance->getTable();
        $default   = config('translatable.default_locale', config('app.fallback_locale', 'en'));

        $translationsToInsert = [];

        DB::transaction(function () use (
            $modelClass,
            $records,
            $attributes,
            $table,
            $default,
            &$translationsToInsert
        ) {
            foreach ($records as $record) {
                $id = $record['id'] ?? null;

                if (!$id) {
                    continue;
                }

                foreach ($attributes as $attribute) {
                    $values = $record[$attribute] ?? null;

                    if (!is_array($values)) {
                        continue;
                    }

                    foreach ($values as $locale => $value) {
                        // Default locale → update the model column directly
                        if ($locale === $default) {
                            $modelClass::where('id', $id)->update([$attribute => $value]);
                            continue;
                        }

                        // Other locales → queue for bulk insert
                        $translationsToInsert[] = [
                            'table_name'  => $table,
                            'foreign_key' => $id,
                            'column_name' => $attribute,
                            'locale'      => $locale,
                            'value'       => $value,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ];
                    }
                }
            }

            // Deduplicate and bulk insert — single query!
            if (!empty($translationsToInsert)) {
                $unique = collect($translationsToInsert)
                    ->unique(fn($item) =>
                        $item['table_name'] . '|' .
                        $item['foreign_key'] . '|' .
                        $item['column_name'] . '|' .
                        $item['locale']
                    )
                    ->values()
                    ->all();

                Translation::insert($unique);
            }
        });
    }

    /**
     * Prepare translations array for bulk insert without saving.
     * Useful when you want to batch multiple models together.
     */
    public static function prepare(string $table, int $id, string $attribute, array $translations): array
    {
        $default = config('translatable.default_locale', config('app.fallback_locale', 'en'));
        $result  = [];

        foreach ($translations as $locale => $value) {
            if ($locale === $default) {
                continue;
            }

            $result[] = [
                'table_name'  => $table,
                'foreign_key' => $id,
                'column_name' => $attribute,
                'locale'      => $locale,
                'value'       => $value,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        return $result;
    }

    /**
     * Flush prepared translations in a single bulk insert.
     */
    public static function flush(array $translations): void
    {
        if (empty($translations)) {
            return;
        }

        $unique = collect($translations)
            ->unique(fn($item) =>
                $item['table_name'] . '|' .
                $item['foreign_key'] . '|' .
                $item['column_name'] . '|' .
                $item['locale']
            )
            ->values()
            ->all();

        Translation::insert($unique);
    }
}