<?php

namespace Scarf\Concerns;

use Scarf\Scarf;

trait Applicable
{
    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return '1.0.0';
    }

    /**
     * Get the base path of the Scarf installation.
     *
     * @return string
     */
    public function basePath()
    {
        return APP_PATH;
    }

    /**
     * Get or check the current application environment.
     *
     * @param  mixed
     *
     * @return string
     */
    public function environment()
    {
        return 'local';
    }

    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return false;
    }

    /**
     * Add middleware to the request.
     *
     * @param string|array $middleware
     */
    public function add($middleware)
    {
        foreach ((array) $middleware as $mid) {
            $this->middlewares[] = $mid;
        }
    }

    /**
     * Register all of the configured providers.
     */
    public function registerConfiguredProviders()
    {
        foreach ($this->providers as $provider) {
            value(new $provider($this))->register();
        }
    }

    /**
     * Register Class Bindings.
     */
    public function registerBaseClassesBinding()
    {
        foreach ($this->baseBinding as $bindings) {
            $this->singleton($bindings[0], $bindings[1]);
        }
    }

    /**
     * Register Custom Available Bindings.
     */
    public function registerCoreBinding()
    {
        foreach ($this->coreBinding as $bindings) {
            $this->singleton($bindings[0], function ($app) use ($bindings) {
                return call_user_func_array([$app, $bindings[1]], []);
            });
        }
    }

    /**
     * Register a service provider with the application.
     *
     * @param array|string $provider
     * @param array        $options
     * @param bool         $force
     */
    public function register($provider, $options = [], $force = false)
    {
        foreach ((array) $provider as $service) {
            $this->providers[] = $service;
        }
    }

    /**
     * Register a deferred provider and service.
     *
     * @param array|string $provider
     * @param string       $service
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        foreach ((array) $provider as $service) {
            $this->providers[] = $service;
        }
    }

    /**
     * Register application constants.
     */
    public function registerConstants()
    {
        define('APP_PATH', dirname($_SERVER['DOCUMENT_ROOT']));

        define('CONFIG_PATH', APP_PATH.DIRECTORY_SEPARATOR.'config');

        define('PUBLIC_PATH', APP_PATH.DIRECTORY_SEPARATOR.'public');

        define('STORAGE_PATH', APP_PATH.DIRECTORY_SEPARATOR.'storage');
    }

    /**
     * Boot the application's service providers.
     */
    public function boot()
    {
        $this->booting(function (Scarf $app) {});

        $this->registerConstants();

        $this->registerBaseClassesBinding();

        $this->registerCoreBinding();

        $this->registerErrorHandling();

        $this->booted(function (Scarf $app) {});
    }

    /**
     * Register a new boot listener.
     *
     * @param mixed $callback
     */
    public function booting($callback)
    {
        call_user_func_array($callback, [$this]);
    }

    /**
     * Register a new "booted" listener.
     *
     * @param mixed $callback
     */
    public function booted($callback)
    {
        call_user_func_array($callback, [$this]);
    }

    /**
     * Get the path to the cached "compiled.php" file.
     *
     * @return string
     */
    public function getCachedCompilePath()
    {
        return STORAGE_PATH.'/framework/cache/';
    }

    /**
     * Get the path to the cached services.json file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        return STORAGE_PATH.'/framework/cache/';
    }
}
