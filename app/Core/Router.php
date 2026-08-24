<?php
namespace App\Core;

/**
 * A small router supporting dynamic segments such as /projects/{id}/edit
 */
class Router
{
    private array $routes = [];
    private $notFound = null;

    public function get(string $path, $handler, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): self
    {
        return $this->add('POST', $path, $handler, $middleware);
    }

    public function put(string $path, $handler, array $middleware = []): self
    {
        return $this->add('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, $handler, array $middleware = []): self
    {
        return $this->add('DELETE', $path, $handler, $middleware);
    }

    /** Registers the same handler for both GET and POST */
    public function any(string $path, $handler, array $middleware = []): self
    {
        foreach (['GET', 'POST'] as $m) {
            $this->add($m, $path, $handler, $middleware);
        }
        return $this;
    }

    public function add(string $method, string $path, $handler, array $middleware = []): self
    {
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $this->compile($path),
            'path'       => $path,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
        return $this;
    }

    public function fallback(callable $handler): self
    {
        $this->notFound = $handler;
        return $this;
    }

    private function compile(string $path): string
    {
        $path = '/' . trim($path, '/');
        if ($path === '/') {
            return '#^/$#';
        }
        // {id} -> any run of characters that is not a slash
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    public function dispatch(Request $request): void
    {
        $pathMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['pattern'], $request->path, $matches)) {
                continue;
            }
            $pathMatched = true;
            if ($route['method'] !== $request->method) {
                continue;
            }

            $params = [];
            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $params[$key] = $value;
                }
            }

            foreach ($route['middleware'] as $mw) {
                // Middleware returning false stops here (it has already redirected)
                if (call_user_func($mw, $request) === false) {
                    return;
                }
            }

            $this->invoke($route['handler'], $request, $params);
            return;
        }

        // Path matched but the HTTP method did not
        $status = $pathMatched ? 405 : 404;
        http_response_code($status);
        if ($this->notFound) {
            call_user_func($this->notFound, $request, $status);
        } else {
            echo $pathMatched ? '405 Method Not Allowed' : '404 Not Found';
        }
    }

    private function invoke($handler, Request $request, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func($handler, $request, $params);
            return;
        }

        // The 'SomeController@method' form
        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $fqcn = 'App\Controllers\\' . $class;

            if (!class_exists($fqcn)) {
                throw new \RuntimeException("Controller not found: {$fqcn}");
            }
            $instance = new $fqcn();
            if (!method_exists($instance, $method)) {
                throw new \RuntimeException("Controller {$fqcn} has no {$method}() method");
            }
            $instance->$method($request, $params);
            return;
        }

        throw new \RuntimeException('Invalid route handler.');
    }
}
