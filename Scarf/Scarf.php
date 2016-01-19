<?php

namespace Scarf;

use Monolog\Logger;
use FastRoute\Dispatcher;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use FastRoute\RouteCollector;
use Scarf\Exceptions\Handler;
use Scarf\Concerns\Applicable;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Container\Container;
use Symfony\Component\Finder\Finder;
use Scarf\Concerns\RouteRegistration;
use Illuminate\Support\Facades\Facade;
use Illuminate\Config\Repository as Config;
use Scarf\Concerns\RegisterExceptionHandlers;
use Illuminate\Contracts\Foundation\Application;
use FastRoute\Dispatcher\GroupCountBased as RouteDispatcher;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class Scarf extends Container implements Application
{
    use Applicable,
        RouteRegistration,
        RegisterExceptionHandlers;

    /**
     * Class binding.
     *
     * @var array
     */
    protected $baseBinding = [
        [['FastRoute\RouteParser\Std' => 'route.parser'], 'FastRoute\RouteParser\Std'],
        [['Illuminate\Http\JsonResponse' => 'response'], 'Illuminate\Http\JsonResponse'],
        [['Illuminate\Contracts\Debug\ExceptionHandler' => 'handler'], 'Scarf\Exceptions\Handler'],
        [['FastRoute\DataGenerator\GroupCountBased' => 'route.data'], 'FastRoute\DataGenerator\GroupCountBased'],
    ];

    /**
     * Custom Available Bindings.
     *
     * @var array
     */
    protected $coreBinding = [
        [['Psr\Log\LoggerInterface' => 'log'], 'registerLogBinding'],
        [['Illuminate\Http\Request' => 'request'], 'registerRequestBinding'],
        [['FastRoute\RouteCollector' => 'route'], 'registerRouteCollectorBinding'],
        [['Illuminate\Contracts\Config\Repository' => 'config'], 'registerConfigBinding'],
        [['Illuminate\Contracts\Foundation\Application' => 'app'], 'registerApplicationBinding'],
        [['FastRoute\Dispatcher\GroupCountBased' => 'route.dispatcher'], 'registerRouteDispatcherBinding'],
        [['Illuminate\Session\SessionManager' => 'session.manager'], 'registerSessionManagerBinding'],
    ];

    /**
     * Service Provider.
     *
     * @var array
     */
    protected $providers = [];

    /**
     * Application middleware.
     *
     * @var array
     */
    protected $middlewares = [];

    /**
     * Register the facades for the application.
     */
    public function withFacades()
    {
        Facade::setFacadeApplication($this);

        class_alias('Illuminate\Support\Facades\DB',       'DB');
        class_alias('Illuminate\Support\Facades\App',      'App');
        class_alias('Illuminate\Support\Facades\File',     'File');
        class_alias('Illuminate\Support\Facades\Cache',    'Cache');
        class_alias('Illuminate\Support\Facades\Event',    'Event');
        class_alias('Illuminate\Support\Facades\Session',  'Session');
        class_alias('Illuminate\Support\Facades\Request',  'Request');
        class_alias('Illuminate\Support\Facades\Response', 'Response');
    }

    /**
     * Register Route Dispatcher Binding.
     *
     * @return \FastRoute\Dispatcher\GroupCountBased
     */
    protected function registerRouteDispatcherBinding()
    {
        $config = $this->make('config');

        if ($config->get('router.cache.enabled')) {
            if (file_exists($cachePath = $config->get('router.cache.path'))) {
                $data = $this->getCache($cachePath);
            } else {
                $data = $this->make('route')->getData();

                $this->writeCache($cachePath, $data);
            }
        } else {
            $data = $this->make('route')->getData();
        }

        return new RouteDispatcher($data);
    }

    /**
     * Register Route Collector Binding.
     *
     * @return \FastRoute\RouteCollector\RouteCollector
     */
    protected function registerRouteCollectorBinding()
    {
        return new RouteCollector($this->make('route.parser'), $this->make('route.data'));
    }

    /**
     * Capture application request.
     */
    protected function registerRequestBinding()
    {
        return Request::capture();
    }

    /**
     * Register application binding.
     */
    protected function registerApplicationBinding()
    {
        return $this;
    }

    protected function registerExceptionHandlerBinding()
    {
        return $this->make('Scarf\Exceptions\Handler');
    }

    /**
     * Register Log Binding.
     *
     * @return \Monolog\Logger
     */
    protected function registerLogBinding()
    {
        return new Logger('scarf', [$this->getMonologHandler()]);
    }

    /**
     * Register Session Manager Binding.
     *
     * @return \Illuminate\Session\SessionManager
     */
    protected function registerSessionManagerBinding()
    {
        return $this->make('session');
    }

    /**
     * Register Configuration Binding.
     */
    protected function registerConfigBinding()
    {
        if (!file_exists(CONFIG_PATH.'/config.php')) {
            return new Config([]);
        }

        $configuration = require CONFIG_PATH.'/config.php';

        $cacheEnabled = $configuration['cache']['enabled'];
        $cachePath = $configuration['cache']['path'];

        if ($cacheEnabled and file_exists($cachePath)) {
            $data = $this->getCache($cachePath);

            return new Config(is_array($data) ? $data : []);
        }

        $configurations = $this->getConfigurationFiles();

        if ($cacheEnabled) {
            $this->writeCache($cachePath, $configurations);
        }

        return new Config($configurations);
    }

    /**
     * Write to cache.
     *
     * @param string $cachePath
     * @param array  $content
     */
    protected function writeCache($cachePath, array $content)
    {
        file_put_contents(
            $cachePath,
            '<?php return '.var_export($content, true).';'
        );
    }

    /**
     * Get cache data.
     *
     * @param string $cachePath
     *
     * @return mixed
     */
    protected function getCache($cachePath)
    {
        $data = require $cachePath;

        return $data;
    }

    /**
     * Gather configurations.
     *
     * @return array
     */
    protected function getConfigurationFiles()
    {
        $configurations = [];

        foreach (Finder::create()
            ->files()
            ->name('*.php')
            ->in(CONFIG_PATH) as $file) {
            $configurations[$file->getBasename('.php')] = require $file->getPathname();
        }

        return $configurations;
    }

    /**
     * Run the application.
     */
    public function run()
    {
        $this->handle($this->make('request'))->send();
    }

    /**
     * Handle a request.
     *
     * @param \Illuminate\Http\Request|null $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request = null)
    {
        $this->registerConfiguredProviders();

        if (!$request) {
            $request = $this->make('request');
        }

        return $this->terminate($request, (new Pipeline($this))
            ->send($request)
            ->through($this->middlewares)
            ->then($this->dispatch())
        );
    }

    /**
     * Dispatch Route Info.
     *
     * @param Request $request
     *
     * @return array
     */
    public function parseRequest(Request $request)
    {
        return $this->make('route.dispatcher')->dispatch($request->getMethod(), $request->getPathInfo());
    }

    /**
     * Get data from route dispatcher.
     *
     * @param array $routeInfo
     *
     * @return array
     */
    protected function getRouteData(array $routeInfo)
    {
        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return [
                    'status' => 404,
                    'message' => 'Not found.',
                ];

                break;
            case Dispatcher::METHOD_NOT_ALLOWED:
                return [
                    'status' => 405,
                    'message' => 'Method not allowed.',
                ];

                break;
            case Dispatcher::FOUND:
                return $this->invokeCallback($routeInfo[1], $routeInfo[2]);

                break;
        }

        return [];
    }

    /**
     * Invoke route dispatcher callback.
     *
     * @param mixed $callable
     * @param array $params
     *
     * @return array|mixed
     */
    protected function invokeCallback($callable, array $params)
    {
        if (is_string($callable)) {
            list($controller, $method) = Str::parseCallback($callable, 'index');

            $callable = [$this->make($controller), $method];
        }

        return call_user_func_array($callable, $params);
    }

    /**
     * Terminate application request.
     *
     * @param \Symfony\Component\HttpFoundation\Request  $request
     * @param \Symfony\Component\HttpFoundation\Response $response
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function terminate(Request $request, BaseResponse $response)
    {
        foreach ($this->middlewares as $middlewareName) {
            $this->make($middlewareName)->terminate($request, $response);
        }

        return $response;
    }

    /**
     * Dispatch route.
     *
     * @return function
     */
    protected function dispatch()
    {
        return function (Request $request) {
            return $this
                ->make('response')
                ->setData($this->getRouteData($this->parseRequest($request)));
        };
    }
}
