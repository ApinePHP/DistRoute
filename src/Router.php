<?php
/**
 * Router
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

namespace Apine\DistRoute;

use Exception;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Apine\DistRoute\Route;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function call_user_func;
use function sprintf;

final class Router implements RequestHandlerInterface
{
    /**
     * @var Route[]
     */
    private $routes = [];
    
    /**
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * @var string
     */
    private $basePattern;
    
    public static $verbs = [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'HEAD',
        'OPTIONS',
        'TRACE',
        'PATCH'
    ];
    
    /**
     * Router constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    
    /**
     * Return the base pattern used when add new routes
     *
     * @return string
     */
    public function getBasePattern() : string
    {
        return $this->basePattern;
    }
    
    /**
     * Set the base pattern added to the pattern of a new route
     * Changing this value will only affect the route to be added
     * after changing it.
     *
     * @param string $pattern
     */
    public function setBasePattern(string $pattern): void
    {
        $this->basePattern = $pattern;
    }
    
    /**
     * Dispatch a request
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws Exception
     * @throws \RuntimeException If no matching route found
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $container = $this->container;
            $route = null;
    
            foreach ($this->routes as $item) {
                if ($item->match($request)) {
                    $route = $item;
                    break;
                }
            }
    
            if (null === $route) {
                throw new RuntimeException(sprintf('Route for request %s not found', $request->getUri()->getPath()));
            }
    
            return $route->invoke($request, $container);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Add route
     *
     * @param string[]        $methods
     * @param string          $pattern
     * @param callable|string $callable
     *
     * @return Route
     */
    public function map(array $methods, string $pattern, $callable): Route
    {
        $route = new Route($methods, $pattern, $callable);
        $this->routes[] = $route;
        
        return $route;
    }
    
    /**
     * Add multiple routes under a prefix
     *
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function group(string $pattern, callable $callable): void
    {
        $currentBase = $this->basePattern;
        $this->basePattern .= $pattern;
        
        call_user_func($callable, $this);
        
        $this->basePattern = $currentBase;
    }
    
    /**
     * Add a route responding to the GET method
     *
     * @param string          $pattern
     * @param callable|string $callable
     *
     * @return Route
     */
    public function get(string $pattern, $callable): Route
    {
        return $this->map(['GET'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the POST method
     *
     * @param string          $pattern
     * @param callable|string $callable
     *
     * @return Route
     */
    public function post(string $pattern, $callable): Route
    {
        return $this->map(['POST'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the PUT method
     *
     * @param string          $pattern
     * @param callable|string $callable
     *
     * @return Route
     */
    public function put(string $pattern, $callable): Route
    {
        return $this->map(['PUT'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the DELETE method
     *
     * @param string          $pattern
     * @param callable|string $callable
     *
     * @return Route
     */
    public function delete(string $pattern, $callable): Route
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the OPTION method
     *
     * @param string          $pattern
     * @param callable|string $callable
     *
     * @return Route
     */
    public function options(string $pattern, $callable): Route
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the HEAD method
     *
     * @param string          $pattern
     * @param callable|string $callable
     *
     * @return Route
     */
    public function head(string $pattern, $callable): Route
    {
        return $this->map(['HEAD'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the TRACE method
     *
     * @param string          $pattern
     * @param callable|string $callable
     *
     * @return Route
     */
    public function trace(string $pattern, $callable): Route
    {
        return $this->map(['TRACE'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the PATCH method
     *
     * @param string          $pattern
     * @param callable|string $callable
     *
     * @return Route
     */
    public function patch(string $pattern, $callable): Route
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to any request method
     *
     * @param string          $pattern
     * @param callable|string $callable
     *
     * @return Route
     */
    public function any(string $pattern, $callable): Route
    {
        return $this->map(self::$verbs, $pattern, $callable);
    }
}