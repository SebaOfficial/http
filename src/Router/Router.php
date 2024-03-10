<?php

declare(strict_types=1);

namespace Seba\HTTP\Router;

use Seba\HTTP\IncomingRequestHandler;
use Seba\HTTP\ResponseHandler;

/**
 * Router Class.
 * This class provides methods routing incoming requests.
 *
 * @package Seba\HTTP\Router
 * @author Sebastiano Racca
 */
class Router
{
    private IncomingRequestHandler $request;
    private ResponseHandler $response;

    private array $routes;
    private array $errorHandlers;

    public function __construct(IncomingRequestHandler $request, ResponseHandler $response)
    {
        $this->routes = [];
        $this->errorHandlers = [];

        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Store a route and a handling function to be executed when accessed using one of the specified methods.
     *
     * @param RequestedMethods|int $methods Allowed methods (i.e. RequestedMethods::GET | RequestedMethods::POST).
     * @param string $pattern A route pattern (i.e. /about). You can pass a regex.
     * @param object|callable $fn The handling function to be executed.
     */
    public function match(int $methods, string $pattern, callable|object $fn): void
    {
        $methodStrings = RequestedMethods::getStrings($methods);

        foreach ($methodStrings as $methodString) {
            $this->routes[$pattern][$methodString] = $fn;
        }
    }

    /**
     * Shorthand for a route accessed using any method.
     *
     * @param string $pattern A route pattern (i.e. /about). You can pass a regex.
     * @param object|callable $fn The handling function to be executed.
     */
    public function all(string $pattern, callable|object $fn): void
    {
        $this->match(
            RequestedMethods::GET |
                RequestedMethods::POST |
                RequestedMethods::PUT |
                RequestedMethods::DELETE |
                RequestedMethods::OPTIONS |
                RequestedMethods::PATCH |
                RequestedMethods::HEAD,
            $pattern,
            $fn
        );
    }

    /**
     * Shorthand for a route accessed using GET.
     *
     * @param string $pattern A route pattern (i.e. /about). You can pass a regex.
     * @param object|callable $fn The handling function to be executed.
     */
    public function get(string $pattern, callable|object $fn): void
    {
        $this->match(RequestedMethods::GET, $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using POST.
     *
     * @param string $pattern A route pattern (i.e. /about). You can pass a regex.
     * @param object|callable $fn The handling function to be executed.
     */
    public function post(string $pattern, callable|object $fn): void
    {
        $this->match(RequestedMethods::POST, $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using PUT.
     *
     * @param string $pattern A route pattern (i.e. /about). You can pass a regex.
     * @param object|callable $fn The handling function to be executed.
     */
    public function put(string $pattern, callable|object $fn): void
    {
        $this->match(RequestedMethods::PUT, $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using DELETE.
     *
     * @param string $pattern A route pattern (i.e. /about). You can pass a regex.
     * @param object|callable $fn The handling function to be executed.
     */
    public function delete(string $pattern, callable|object $fn): void
    {
        $this->match(RequestedMethods::DELETE, $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using OPTIONS.
     *
     * @param string $pattern A route pattern (i.e. /about). You can pass a regex.
     * @param object|callable $fn The handling function to be executed.
     */
    public function options(string $pattern, callable|object $fn): void
    {
        $this->match(RequestedMethods::OPTIONS, $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using HEAD.
     *
     * @param string $pattern A route pattern (i.e. /about). You can pass a regex.
     * @param object|callable $fn The handling function to be executed.
     */
    public function head(string $pattern, callable|object $fn): void
    {
        $this->match(RequestedMethods::HEAD, $pattern, $fn);
    }

    /**
     * Shorthand for a route accessed using PATCH.
     *
     * @param string $pattern A route pattern (i.e. /about). You can pass a regex.
     * @param object|callable $fn The handling function to be executed.
     */
    public function patch(string $pattern, callable|object $fn): void
    {
        $this->match(RequestedMethods::PATCH, $pattern, $fn);
    }

    /**
     * Mounts a collection of callbacks onto a base route.
     *
     * @param string $pattern A route pattern (i.e. /about). You can pass a regex.
     * @param callable $fn The callback method.
     */
    public function mount(string $basePath, callable $fn): void
    {
        $router = new Router($this->request, $this->response);

        $fn($router);

        $this->registerHandlers($basePath, $router->getRoutes(), $this->routes);
        $this->registerHandlers($basePath, $router->getErrorHandlers(), $this->errorHandlers);
    }

    private function registerHandlers(string $basePath, array $handlers, array &$target): void
    {
        foreach ($handlers as $pattern => $methods) {
            foreach ($methods as $method => $handler) {
                $target[$basePath . $pattern][$method] = $handler;
            }
        }
    }

    /**
     * Set an error handling function.
     *
     * @param int $httpStatusCode The error code.
     * @param object|callable $fn The function to be executed.
     * @param string $pattern A route pattern (i.e. /about). You can pass a regex.
     */
    public function onError(int $httpStatusCode, callable|object $fn, string $pattern = '/?.*'): void
    {
        $this->errorHandlers[$pattern][$httpStatusCode] = $fn;
    }

    /**
     * Triggers an error handling function.
     *
     * @param int $httpStatusCode The error code.
     */
    public function triggerError(int $httpStatusCode): void
    {
        $currentRoute = $this->request->getUri();

        foreach ($this->errorHandlers as $pattern => $httpCodes) {
            if (preg_match("#^$pattern$#", $currentRoute, $matches)) {
                $requestedMethods = array_keys($httpCodes);
                if (in_array($httpStatusCode, $requestedMethods)) {
                    $handler = $httpCodes[$httpStatusCode];
                    if (is_callable($handler)) {
                        unset($matches[0]);
                        $handler(...$matches);
                    }
                }
            }
        }

        if (isset($this->errorHandlers[$httpStatusCode][$pattern]) && preg_match("#^$pattern$#", $pattern, $matches)) {
            $this->errorHandlers[$httpStatusCode][$pattern](...$matches);
        }

        $this->response->setHttpCode($httpStatusCode)->send();
    }

    /**
     * Get all defined routes.
     *
     * @return array An array of defined routes.
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Get all defined error handlers.
     *
     * @return array An array of defined error handlers.
     */
    public function getErrorHandlers(): array
    {
        return $this->errorHandlers;
    }

    /**
     * Execute the router: Loop all defined before middleware's and routes, and execute the handling function if a match was found.
     *
     * @param object|callable $callback Function to be executed after a matching route was handled (= after router middleware)
     *
     * @return bool True if the route was handled. False otherwise.
     */
    public function run(): bool
    {
        $currentRoute = $this->request->getUri();
        $method = $this->request->getMethod();

        foreach ($this->routes as $pattern => $handlers) {
            if (preg_match("#^$pattern$#", $currentRoute, $matches)) {
                $requestedMethods = array_keys($handlers);
                if (in_array($method, $requestedMethods)) {
                    $handler = $handlers[$method];
                    if (is_callable($handler)) {
                        unset($matches[0]);
                        $handler(...$matches);
                        return true;
                    }
                }
            }
        }

        $this->triggerError(404);

        return false;
    }

}
