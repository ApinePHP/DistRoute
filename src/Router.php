<?php
/**
 * Router
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

namespace Apine\DistRoute;

use ReflectionClass;
use RuntimeException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function call_user_func;

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
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    public function getBasePattern() : string
    {
        return $this->basePattern;
    }
    
    public function setBasePattern(string $pattern): void
    {
        $this->basePattern = $pattern;
    }
    
    /**
     * Find the best matching controller and action for the request
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
     * @return ResponseInterface
     * @throws \Exception
     */
    private function execute() : ResponseInterface
    {
        try {
            $container = $this->container;
            $route = $this->current;
            $request = $this->request;
            
            $reflection = new ReflectionClass($route->controller);
            $constructor = $reflection->getConstructor();
            
            $method = $reflection->getMethod($route->action);
            $requestParams = DependencyResolver::mapParametersForRequest($request, $route);
            
            /* Execution of the user code
             *
             * Instantiate de controller then
             * call the action method
             */
            if ($constructor !== null) {
                $constructorParameters = DependencyResolver::mapConstructorArguments($container, $constructor->getParameters());
                
                $controller = $reflection->newInstanceArgs($constructorParameters);
            } else {
                $controller = $reflection->newInstanceWithoutConstructor();
            }
            
            $parameters = DependencyResolver::mapActionArguments($container, $requestParams, $route->actionParameters);
            
            $response = $method->invokeArgs($controller, $parameters);
    
            if (!($response instanceof ResponseInterface)) {
                throw new \RuntimeException(sprintf('%s::%s must return an instance of %s', $route->controller, $route->action, ResponseInterface::class));
            }
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * @param Route $route
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function run(Route $route, ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $this->current = $route;
            $this->request = $request;
            
            if (!$this->current->match($request)) {
                throw new \Exception(sprintf('Route does not match request %s', $request->getUri()->getPath()), 404);
            }
            
            return $this->execute();
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function dispatch(ServerRequestInterface $request) : ResponseInterface
    {
        try {
            $this->find($request);
            return $this->execute();
        } catch (\Exception $e) {
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
    public function map(array $methods, string $pattern, $callable)
    {
        /*if (!class_exists($controller) || !method_exists($controller, $action)) {
            throw new \Exception('Controller or method not found');
        }*/
        
        /*foreach ($methods as $method) {
            $this->routes[] = new Route($method, $pattern, $callable);
        }*/
    
        $this->routes[] = new Route($methods, $pattern, $callable);
    }
    
    /**
     * Add multiple routes under a prefix
     *
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function group(string $pattern, callable $callable)
    {
        $currentBase = $this->basePattern;
        $this->basePattern .= $pattern;
        
        call_user_func($callable);
        
        $this->basePattern = $currentBase;
    }
    
    /**
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function get(string $pattern, $callable)
    {
        $this->map(['GET'], $pattern, $callable);
    }
    
    /**
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function post(string $pattern, $callable)
    {
        $this->map(['POST'], $pattern, $callable);
    }
    
    /**
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function put(string $pattern, $callable)
    {
        $this->map(['PUT'], $pattern, $callable);
    }
    
    /**
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function delete(string $pattern, $callable)
    {
        $this->map(['DELETE'], $pattern, $callable);
    }
    
    /**
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function options(string $pattern, $callable)
    {
        $this->map(['OPTIONS'], $pattern, $callable);
    }
    
    /**
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function head(string $pattern, $callable)
    {
        $this->map(['HEAD'], $pattern, $callable);
    }
    
    /**
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function trace(string $pattern, $callable)
    {
        $this->map(['TRACE'], $pattern, $callable);
    }
    
    /**
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function patch(string $pattern, $callable)
    {
        $this->map(['PATCH'], $pattern, $callable);
    }
    
    /**
     * @param string          $pattern
     * @param callable|string $callable
     */
    public function any(string $pattern, $callable)
    {
        $this->map(self::$verbs, $pattern, $callable);
    }
}