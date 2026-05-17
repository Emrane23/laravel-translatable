<?php

namespace Emrane23\Translatable\Tests\Feature;

use Emrane23\Translatable\Helpers\TranslationSeeder;
use Emrane23\Translatable\Tests\Fixtures\Product;
use Emrane23\Translatable\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TranslatableTest extends TestCase
{
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a product with default locale (fr)
        $this->product = Product::create([
            'name'        => 'Ordinateur portable',
            'description' => 'Puissant et léger',
            'price'       => 999.99,
        ]);

        // Seed translations
        TranslationSeeder::bulkSeed(Product::class, [
            [
                'id'          => $this->product->id,
                'name'        => [
                    'fr' => 'Ordinateur portable',
                    'en' => 'Laptop',
                    'ar' => 'حاسوب محمول',
                    'es' => 'Portátil',
                ],
                'description' => [
                    'fr' => 'Puissant et léger',
                    'en' => 'Powerful & light',
                    'ar' => 'قوي وخفيف',
                    'es' => 'Potente y ligero',
                ],
            ],
        ], ['name', 'description']);
    }

    // =========================================================================
    // 1. Magic getter
    // =========================================================================

    #[Test]
    public function it_returns_default_locale_from_column(): void
    {
        app()->setLocale('fr');

        $this->assertEquals('Ordinateur portable', $this->product->fresh()->name);
    }

    #[Test]
    public function it_returns_translation_for_requested_locale(): void
    {
        app()->setLocale('en');

        $this->assertEquals('Laptop', $this->product->fresh()->name);
    }

    #[Test]
    public function it_returns_arabic_translation(): void
    {
        app()->setLocale('ar');

        $this->assertEquals('حاسوب محمول', $this->product->fresh()->name);
    }

    #[Test]
    public function it_returns_spanish_translation(): void
    {
        app()->setLocale('es');

        $this->assertEquals('Portátil', $this->product->fresh()->name);
    }

    // =========================================================================
    // 2. Fallback
    // =========================================================================

    #[Test]
    public function it_falls_back_to_fallback_locale_when_translation_missing(): void
    {
        app()->setLocale('de'); // German — not seeded

        // fallback_locale = en → should return "Laptop"
        $this->assertEquals('Laptop', $this->product->fresh()->name);
    }

    #[Test]
    public function it_falls_back_to_default_column_when_both_locales_missing(): void
    {
        app()->setLocale('de'); // German — not seeded

        // Delete fallback (en) translation too
        $this->product->deleteAttributeTranslation('name', 'en');

        // Should return default column value (fr)
        $this->assertEquals('Ordinateur portable', $this->product->fresh()->name);
    }

    // =========================================================================
    // 3. getTranslatedAttribute
    // =========================================================================

    #[Test]
    public function it_gets_translated_attribute_for_explicit_locale(): void
    {
        $this->assertEquals('Laptop', $this->product->getTranslatedAttribute('name', 'en'));
        $this->assertEquals('Portátil', $this->product->getTranslatedAttribute('name', 'es'));
        $this->assertEquals('حاسوب محمول', $this->product->getTranslatedAttribute('name', 'ar'));
    }

    #[Test]
    public function it_gets_default_locale_without_fallback(): void
    {
        // No fallback — locale missing → should return default column
        $value = $this->product->getTranslatedAttribute('name', 'de', false);

        $this->assertEquals('Ordinateur portable', $value);
    }

    // =========================================================================
    // 4. getTranslatedAttributeMeta
    // =========================================================================

    #[Test]
    public function it_returns_correct_meta_for_existing_translation(): void
    {
        [$value, $locale, $found] = $this->product->getTranslatedAttributeMeta('name', 'en');

        $this->assertEquals('Laptop', $value);
        $this->assertEquals('en', $locale);
        $this->assertTrue($found);
    }

    #[Test]
    public function it_returns_correct_meta_for_default_locale(): void
    {
        [$value, $locale, $found] = $this->product->getTranslatedAttributeMeta('name', 'fr');

        $this->assertEquals('Ordinateur portable', $value);
        $this->assertEquals('fr', $locale);
        $this->assertTrue($found);
    }

    #[Test]
    public function it_returns_correct_meta_when_translation_missing(): void
    {
        [$value, $locale, $found] = $this->product->getTranslatedAttributeMeta('name', 'de');

        // Fallback to 'en'
        $this->assertEquals('Laptop', $value);
        $this->assertEquals('en', $locale);
        $this->assertTrue($found);
    }

    // =========================================================================
    // 5. setAttributeTranslations
    // =========================================================================

    #[Test]
    public function it_sets_translations_for_multiple_locales(): void
    {
        $this->product->setAttributeTranslations('name', [
            'en' => 'Portable Computer',
            'es' => 'Computadora portátil',
        ]);

        $this->assertEquals('Portable Computer', $this->product->fresh()->getTranslatedAttribute('name', 'en'));
        $this->assertEquals('Computadora portátil', $this->product->fresh()->getTranslatedAttribute('name', 'es'));
    }

    #[Test]
    public function it_updates_default_locale_column_directly(): void
    {
        $this->product->setAttributeTranslations('name', [
            'fr' => 'Nouveau nom',
        ], save: true);

        $this->assertEquals('Nouveau nom', $this->product->fresh()->getAttributeValue('name'));
    }

    // =========================================================================
    // 6. deleteAttributeTranslation
    // =========================================================================

    #[Test]
    public function it_deletes_a_single_translation(): void
    {
        $this->product->deleteAttributeTranslation('name', 'es');

        // After delete, fallback to 'en'
        app()->setLocale('es');
        $this->assertEquals('Laptop', $this->product->fresh()->name);
    }

    #[Test]
    public function it_deletes_multiple_locales_at_once(): void
    {
        $this->product->deleteAttributeTranslation('name', ['en', 'es']);

        // Both gone → fallback to default column (fr)
        app()->setLocale('en');
        $this->assertEquals('Ordinateur portable', $this->product->fresh()->name);
    }

    #[Test]
    public function it_deletes_multiple_attributes(): void
    {
        $this->product->deleteAttributeTranslations(['name', 'description'], ['en']);

        $this->assertEquals('Ordinateur portable', $this->product->fresh()->getTranslatedAttribute('name', 'en'));
        $this->assertEquals('Puissant et léger', $this->product->fresh()->getTranslatedAttribute('description', 'en'));
    }

    // =========================================================================
    // 7. withTranslation scope
    // =========================================================================

    #[Test]
    public function it_eager_loads_translations_to_avoid_n_plus_1(): void
    {
        // Create more products
        $p2 = Product::create(['name' => 'Souris sans fil', 'price' => 29.99]);
        TranslationSeeder::bulkSeed(Product::class, [
            ['id' => $p2->id, 'name' => ['fr' => 'Souris sans fil', 'en' => 'Wireless Mouse']],
        ], ['name']);

        app()->setLocale('en');

        $products = Product::withTranslation('en')->get();

        $this->assertEquals('Laptop', $products->first()->name);
        $this->assertEquals('Wireless Mouse', $products->last()->name);
    }

    // =========================================================================
    // 8. translatable() and getTranslatableAttributes()
    // =========================================================================

    #[Test]
    public function it_returns_true_for_translatable_check(): void
    {
        $this->assertTrue($this->product->translatable());
    }

    #[Test]
    public function it_returns_list_of_translatable_attributes(): void
    {
        $this->assertEquals(['name', 'description'], $this->product->getTranslatableAttributes());
    }

    // =========================================================================
    // 9. TranslationSeeder
    // =========================================================================

    #[Test]
    public function it_bulk_seeds_translations_correctly(): void
    {
        $p = Product::create(['name' => 'Test', 'price' => 1]);

        TranslationSeeder::bulkSeed(Product::class, [
            ['id' => $p->id, 'name' => ['fr' => 'Test', 'en' => 'Test EN', 'ar' => 'اختبار']],
        ], ['name']);

        $this->assertEquals('Test EN', $p->fresh()->getTranslatedAttribute('name', 'en'));
        $this->assertEquals('اختبار', $p->fresh()->getTranslatedAttribute('name', 'ar'));
    }

    #[Test]
    public function it_deduplicates_translations_on_bulk_seed(): void
    {
        $p = Product::create(['name' => 'Test', 'price' => 1]);

        // Seed twice — should not throw unique constraint error
        TranslationSeeder::bulkSeed(Product::class, [
            ['id' => $p->id, 'name' => ['en' => 'First']],
            ['id' => $p->id, 'name' => ['en' => 'Second']], // duplicate locale
        ], ['name']);

        // Should not crash — deduplication keeps first
        $this->assertNotNull($p->fresh());
    }

    #[Test]
    public function it_uses_prepare_and_flush_correctly(): void
    {
        $p = Product::create(['name' => 'Test', 'price' => 1]);

        $translations = TranslationSeeder::prepare('products', $p->id, 'name', [
            'en' => 'Prepared EN',
            'ar' => 'محضر',
        ]);

        TranslationSeeder::flush($translations);

        $this->assertEquals('Prepared EN', $p->fresh()->getTranslatedAttribute('name', 'en'));
        $this->assertEquals('محضر', $p->fresh()->getTranslatedAttribute('name', 'ar'));
    }
}