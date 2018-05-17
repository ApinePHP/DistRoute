<?php
/**
 * Router
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

namespace Apine\DistRoute;

use Closure;
use Exception;
use RuntimeException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function sprintf;

/**
 * A lightweight fully PSR-7 compatible regex based request router
 * with dependency injection using a DI container
 *
 * @package Apine\DistRoute
 */
final class Router implements RouterInterface
{
    /**
     * List or available routes
     *
     * @var Route[]
     */
    private $routes = [];
    
    /**
     * DI Container
     *
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * Base route pattern
     *
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
        'TRACE'
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
     * Changing this value will only affect the routes to be added
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
     * @throws RouteNotFoundException If no matching route found
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
    
        $container = $this->container;
        $route = null;
    
        foreach ($this->routes as $item) {
            if ($item->match($request)) {
                $route = $item;
                break;
            }
        }
    
        if (null === $route) {
            throw new RouteNotFoundException(sprintf('Route for request %s not found', $request->getUri()->getPath()));
        }
        
        try {
            return $route->invoke($request, $container);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Map a new route to one or multiple request methods
     *
     * @inheritdoc RouteMappingInterface::map()
     *
     * @return Route
     */
    public function map(array $methods, string $pattern, $callable): Route
    {
        $pattern = $this->basePattern . $pattern;
        $route = new Route($methods, $pattern, $callable);
        $this->routes[] = $route;
        
        return $route;
    }
    
    /**
     * Add multiple routes under a prefix
     *
     * @inheritdoc RouteMappingInterface::group()
     */
    public function group(string $pattern, Closure $closure): void
    {
        $currentBase = $this->basePattern;
        $this->basePattern .= $pattern;
        
        $closure->call($this);
        
        $this->basePattern = $currentBase;
    }
    
    /**
     * Add a route responding to the GET method
     *
     * @inheritdoc RouteMappingInterface::get()
     * @see RouterInterface::map()
     * @return Route
     */
    public function get(string $pattern, $callable): Route
    {
        return $this->map(['GET'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the POST method
     *
     * @inheritdoc RouteMappingInterface::post()
     * @see RouterInterface::map()
     * @return Route
     */
    public function post(string $pattern, $callable): Route
    {
        return $this->map(['POST'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the PUT method
     *
     * @inheritdoc RouteMappingInterface::put()
     * @see RouterInterface::map()
     * @return Route
     */
    public function put(string $pattern, $callable): Route
    {
        return $this->map(['PUT'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the DELETE method
     *
     * @inheritdoc RouteMappingInterface::delete()
     * @see RouterInterface::map()
     * @return Route
     */
    public function delete(string $pattern, $callable): Route
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the OPTION method
     *
     * @inheritdoc RouteMappingInterface::options()
     * @see RouterInterface::map()
     * @return Route
     */
    public function options(string $pattern, $callable): Route
    {
        return $this->map(['OPTIONS'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the HEAD method
     *
     * @inheritdoc RouteMappingInterface::head()
     * @see RouterInterface::map()
     * @return Route
     */
    public function head(string $pattern, $callable): Route
    {
        return $this->map(['HEAD'], $pattern, $callable);
    }
    
    /**
     * @inheritdoc RouteMappingInterface::trace()
     * @see RouterInterface::map()
     * @return Route
     */
    public function trace(string $pattern, $callable): Route
    {
        return $this->map(['TRACE'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to any request method
     *
     * @inheritdoc RouteMappingInterface::any()
     * @see RouterInterface::map()
     *
     * @return Route
     */
    public function any(string $pattern, $callable): Route
    {
        $pattern = $this->basePattern . $pattern;
        $route = new Route([], $pattern, $callable);
        $this->routes[] = $route;
    
        return $route;
    }
}