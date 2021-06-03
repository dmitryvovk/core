<?php

namespace Apiato\Core\Providers;

use Apiato\Core\Abstracts\Events\Providers\EventServiceProvider;
use Apiato\Core\Abstracts\Providers\MainProvider as AbstractMainProvider;
use Apiato\Core\Foundation\Apiato;
use Apiato\Core\Loaders\AutoLoaderTrait;
use Apiato\Core\Traits\ValidationTrait;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

/**
 * Class ApiatoProvider
 * Does not have to extend from the Ship parent MainProvider since it's on the Core
 * it directly extends from the Abstract MainProvider.
 */
class ApiatoProvider extends AbstractMainProvider
{
    use AutoLoaderTrait;
    use ValidationTrait;

    private const DEFAULT_STRING_LENGTH = 191;

    public function boot(): void
    {
        parent::boot();

        // Autoload most of the Containers and Ship Components
        $this->runLoadersBoot();

        /**
         * Solves the "specified key was too long" error, introduced in L5.4.
         *
         * @see https://laravel.com/docs/8.x/migrations#index-lengths-mysql-mariadb
         */
        Schema::defaultStringLength(self::DEFAULT_STRING_LENGTH);

        // Registering custom validation rules
        $this->extendValidationRules();
    }

    public function register(): void
    {
        parent::register();

        $this->overrideLaravelBaseProviders();

        // Register Core Facade Classes, should not be registered in the $aliases property, since they are used
        // by the auto-loading scripts, before the $aliases property is executed.
        $this->app->alias(Apiato::class, 'Apiato');
    }

    /**
     * Register Override Base providers.
     *
     * @see \Illuminate\Foundation\Application::registerBaseServiceProviders
     */
    private function overrideLaravelBaseProviders(): void
    {
        App::register(EventServiceProvider::class); //The custom apiato eventserviceprovider
    }
}
