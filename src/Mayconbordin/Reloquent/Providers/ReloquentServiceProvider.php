<?php namespace Mayconbordin\Reloquent\Providers;

use Illuminate\Support\ServiceProvider;

class ReloquentServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../../resources/config/reloquent.php' => config_path('reloquent.php')
        ]);

        $this->mergeConfigFrom(
            __DIR__ . '/../../../resources/config/reloquent.php', 'reloquent'
        );

        $this->loadTranslationsFrom(__DIR__ . '/../../../resources/lang', 'reloquent');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
    }

    public function provides()
    {
        return [];
    }
}