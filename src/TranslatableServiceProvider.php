<?php

namespace Emrane23\Translatable;

use Illuminate\Support\ServiceProvider;

class TranslatableServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/create_translations_table.php' =>
                database_path('migrations/' . date('Y_m_d_His') . '_create_translations_table.php'),
        ], 'translatable-migrations');

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/translatable.php' =>
                config_path('translatable.php'),
        ], 'translatable-config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/translatable.php',
            'translatable'
        );
    }
}