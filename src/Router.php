<?php
/*
 * package   OpenEMR
 * link           https://open-emr.org
 * author      Sherwin Gaddis <sherwingaddis@gmail.com>
 * Copyright (c) 2024.  Sherwin Gaddis <sherwingaddis@gmail.com>
 */

namespace Juggernaut\Module\HospitalCharge;

class Router
{
    private array $routes = [];

    public function add($method, $path, $callback): void
    {
        $path = preg_replace('/\{([a-z]+)\}/', '(?<$1>[a-z0-9\-]+)', $path);
        $path = '#^' . $path . '$#';
        $this->routes[] = ['method' => $method, 'path' => $path, 'callback' => $callback];
    }

    public function run() {
        $requestedMethod = $_SERVER['REQUEST_METHOD'];
        $requestedPath = $_SERVER['REQUEST_URI'];

        foreach ($this->routes as $route) {
            if ($route['method'] == $requestedMethod && preg_match($route['path'], $requestedPath, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                call_user_func_array($route['callback'], $params);
                return;
            }
        }

        // Handle not found case
        http_response_code(404);
        echo '404 Not Found';
    }
}
