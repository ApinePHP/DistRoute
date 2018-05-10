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
use RuntimeException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function call_user_func;
use function sprintf;

class Router implements RouterInterface
{
    /**
     * @var ServerRequestInterface
     */
    private $request;
    
    /**
     * @var Route[]
     */
    private $routes = [];
    
    /**
     * @var Route
     */
    private $current;
    
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
     * Find the best matching route for the request
     *
     * @param ServerRequestInterface $request
     * @return Route
     *
     * @throws \Exception If route not found
     */
    public function find (ServerRequestInterface $request) : Route
    {
        foreach ($this->routes as $route) {
            if ($route->match($request)) {
                $this->current = $route;
                $this->request = $request;
                break;
            }
        }
        
        if (null === $this->current) {
            throw new RuntimeException(sprintf('Route for request %s not found', $request->getUri()->getPath()));
        }
        
        return $this->current;
    }
    
    /**
     * Execute a route's callable
     *
     * @return ResponseInterface
     * @throws Exception
     */
    private function execute(): ResponseInterface
    {
        try {
            $container = $this->container;
            $route = $this->current;
            $request = $this->request;
            
            return $route->invoke($request, $container);
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Run the router
     *
     * @param Route $route
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function run(Route $route, ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $this->current = $route;
            $this->request = $request;
            
            if (!$this->current->match($request)) {
                throw new RuntimeException(sprintf('Route does not match request %s', $request->getUri()->getPath()), 404);
            }
            
            return $this->execute();
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Dispatch a request to a route then return the generated response
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function dispatch(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $this->find($request);
            return $this->execute();
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
     */
    public function map(array $methods, string $pattern, $callable): void
    {
        $this->routes[] = new Route($methods, $pattern, $callable);
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
        
        call_user_func($callable);
        
        $this->basePattern = $currentBase;
    }
    
    /**
     * Add a route responding to the GET method
     *
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function get(string $pattern, $callable): void
    {
        $this->map(['GET'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the POST method
     *
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function post(string $pattern, $callable): void
    {
        $this->map(['POST'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the PUT method
     *
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function put(string $pattern, $callable): void
    {
        $this->map(['PUT'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the DELETE method
     *
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function delete(string $pattern, $callable): void
    {
        $this->map(['DELETE'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the OPTION method
     *
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function options(string $pattern, $callable): void
    {
        $this->map(['OPTIONS'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the HEAD method
     *
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function head(string $pattern, $callable): void
    {
        $this->map(['HEAD'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the TRACE method
     *
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function trace(string $pattern, $callable): void
    {
        $this->map(['TRACE'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to the PATCH method
     *
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function patch(string $pattern, $callable): void
    {
        $this->map(['PATCH'], $pattern, $callable);
    }
    
    /**
     * Add a route responding to any request method
     *
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function any(string $pattern, $callable): void
    {
        $this->map(self::$verbs, $pattern, $callable);
    }
}