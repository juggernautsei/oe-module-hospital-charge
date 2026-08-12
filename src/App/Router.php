<?php

/*
 * package   OpenEMR
 * link           https://open-emr.org
 * author      Sherwin Gaddis <sherwingaddis@gmail.com>
 * Copyright (c) 2024.  Sherwin Gaddis <sherwingaddis@gmail.com>
 */

namespace Juggernaut\Module\HospitalCharge\App;

use Juggernaut\Module\HospitalCharge\App\Exceptions\RouteNotFoundException;

class Router
{
    private array $routes = [];

    /**
     * @param string $requestMethod
     * @param string $route
     * @param callable|array $action
     * @return $this
     */
    public function register(string $requestMethod, string $route, $action): self
    {
        $this->routes[$requestMethod][$route] = $action;
        return $this;
    }

    public function get(string $route, $action): self
    {
        return $this->register('get', $route, $action);
    }

    public function post(string $route, $action): self
    {
        return $this->register('post', $route, $action);
    }

    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @throws RouteNotFoundException
     */
    public function resolve(string $requestUri, string $requestMethod)
    {
        $route = explode("?", $requestUri)[0];

        foreach ($this->routes[$requestMethod] as $path => $action) {
            $pattern = preg_replace('/\{([^\/]+)\}/', '([^\/]+)', $path);
            if (preg_match("#^" . $pattern . "$#", $route, $matches)) {
                array_shift($matches); // Remove full match

                if (is_callable($action)) {
                    return call_user_func_array($action, $matches);
                }

                if (is_array($action)) {
                    [$class, $method] = $action;
                    if (class_exists($class)) {
                        $classInstance = new $class();
                        if (method_exists($classInstance, $method)) {
                            return call_user_func_array([$classInstance, $method], $matches);
                        }
                    }
                }
            }
        }
        throw new RouteNotFoundException("Route not found: " . $route);
    }
}
