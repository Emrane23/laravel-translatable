<?php

namespace Emrane23\Translatable\Tests;

use Emrane23\Translatable\TranslatableServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            TranslatableServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $app['config']->set('translatable.default_locale', 'fr');
        $app['config']->set('translatable.fallback_locale', 'en');
        $app['config']->set('translatable.supported_locales', ['fr', 'en', 'ar', 'es']);

        $app['config']->set('app.locale', 'fr');
        $app['config']->set('app.fallback_locale', 'en');
    }

    protected function setUpDatabase(): void
    {
        // Create translations table
        $this->app['db']->connection()->getSchemaBuilder()->create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('table_name');
            $table->unsignedBigInteger('foreign_key');
            $table->string('column_name');
            $table->string('locale', 10);
            $table->text('value');
            $table->timestamps();

            $table->unique(['table_name', 'foreign_key', 'column_name', 'locale']);
            $table->index(['table_name', 'foreign_key', 'locale']);
        });

        // Create products table for testing
        $this->app['db']->connection()->getSchemaBuilder()->create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();
        });
    }
}