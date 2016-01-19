<?php

namespace Scarf\Concerns;

trait RouteRegistration
{
    /**
     * Register GET route to a specific virtual path.
     *
     * @param string $path
     * @param mixed  $callable
     */
    public function get($path, $callable)
    {
        $this->make('route')->addRoute('GET', $path, $callable);
    }

    /**
     * Register POST route to a specific virtual path.
     *
     * @param string $path
     * @param mixed  $callable
     */
    public function post($path, $callable)
    {
        $this->make('route')->addRoute('POST', $path, $callable);
    }

    /**
     * Register PUT route to a specific virtual path.
     *
     * @param string $path
     * @param mixed  $callable
     */
    public function put($path, $callable)
    {
        $this->make('route')->addRoute('PUT', $path, $callable);
    }

    /**
     * Register PATCH route to a specific virtual path.
     *
     * @param string $path
     * @param mixed  $callable
     */
    public function patch($path, $callable)
    {
        $this->make('route')->addRoute('PATCH', $path, $callable);
    }

    /**
     * Register DELETE route to a specific virtual path.
     *
     * @param string $path
     * @param mixed  $callable
     */
    public function delete($path, $callable)
    {
        $this->make('route')->addRoute('DELETE', $path, $callable);
    }

    /**
     * Register HEAD route to a specific virtual path.
     *
     * @param string $path
     * @param mixed  $callable
     */
    public function head($path, $callable)
    {
        $this->make('route')->addRoute('HEAD', $path, $callable);
    }

    /**
     * Only accept on certain HTTP methods.
     *
     * @param array           $method   Ex: ['POST', 'PATCH'].
     * @param string          $path     Virtual route path.
     * @param string|callable $callable Callback / Controller@method string.
     */
    public function accept(array $method, $path, $callable)
    {
        $this->make('route')->addRoute($method, $path, $callable);
    }

    /**
     * Register ANY route to a specific virtual path.
     *
     * @param string $path
     * @param mixed  $callable
     */
    public function any($path, $callable)
    {
        $this->make('route')->addRoute([
            'GET',
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
            'HEAD',
        ], $path, $callable);
    }

    /**
     * Add resource controller.
     *
     * @param string $path       Base Path of resource controller.
     * @param string $controller Name of controller class.
     */
    public function resource($path, $controller)
    {
        $route = $app->make('route');

        $route->addRoute(['GET'], $path, 'App\Http\Controllers\\'.$controller.'@index');
        $route->addRoute(['PUT', 'POST'], $path, 'App\Http\Controllers\\'.$controller.'@store');
        $route->addRoute(['GET'], $path.'/{id}', 'App\Http\Controllers\\'.$controller.'@show');
        $route->addRoute(['PUT', 'PATCH'], $path.'/{id}', 'App\Http\Controllers\\'.$controller.'@update');
        $route->addRoute(['DELETE'], $path.'/{id}', 'App\Http\Controllers\\'.$controller.'@destroy');
    }
}
