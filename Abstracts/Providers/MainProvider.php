<?php

namespace Apiato\Core\Abstracts\Providers;

use Apiato\Core\Loaders\AliasesLoaderTrait;
use Apiato\Core\Loaders\ProvidersLoaderTrait;
use Illuminate\Support\ServiceProvider as LaravelAppServiceProvider;

/**
 * Class MainProvider.
 *
 * A.K.A (app/Providers/AppServiceProvider.php)
 */
abstract class MainProvider extends LaravelAppServiceProvider
{
    use AliasesLoaderTrait;
    use ProvidersLoaderTrait;

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        $this->loadServiceProviders();
        $this->loadAliases();
    }

    /**
     * Register anything in the container.
     */
    public function register(): void
    {
    }
}
