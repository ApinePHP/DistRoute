<?php
/**
 * Route
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

namespace Apine\DistRoute;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function in_array, array_map, array_walk, count;
use function str_ireplace, preg_match_all, preg_replace, sprintf;
use function is_callable, is_string;

/**
 * Route
 *
 * @package Apine\DistRoute
 */
final class Route
{
    /**
     * Route's pattern
     *
     * @var string
     */
    public $pattern;
    
    /**
     * Accepted request methods
     *
     * @var string[]
     */
    public $methods;
    
    /**
     * Handler to be executed
     *
     * @var callable|string
     */
    private $callable;
    
    /**
     * Built regex for matching
     *
     * @var string
     */
    public $regex;
    
    /**
     * The array of accepted methods MUST be any standard or custom capitalized
     * method names as defined in RFC-7231. Leaving the list empty means that
     * the route accepts any request method
     *
     * The route pattern is a string similar to the uri path as defined in
     * RFC-3986. The parts that MAY be matched and used as parameter for the
     * callable MUST be declared with a name in between curly brackets. A
     * matching pattern as a valid regular expression part MAY by included in
     * parentheses and separated from the parameter name by a colon. A parameter
     * MAY be explicitly identified as optional by placing a question mark before
     * the name of the parameter.
     *
     * Examples of valid route patterns :
     *
     * - /users
     * - /user/{id}
     * - /user/{id:(\d+)}
     * - /user/{id}/{?other}
     *
     * The callable is either an anonymous function, the name of a function, or
     * the name of a class and the name of the method separated with a "@". The
     * name of the controller MUST include be fully qualified (the namespace as
     * well as the class name).
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @param string[]          $methods A list of accepted request methods
     * @param string            $pattern A route pattern similar to /user/{id:(\d+)}
     * @param callable|string   $callable The handler to be executed
     */
    public function __construct(array $methods, string $pattern, $callable)
    {
        $this->pattern = $pattern;
        $this->methods = $methods;
        $this->callable = $callable;
        $this->regex = $this->buildRegex();
    }
    
    /**
     * Parse the route pattern to extract the list of named parameters
     *
     * @param string $pattern A route pattern similar to /user/{id:(\d+)}
     *
     * @return array
     */
    private function parseParameters(string $pattern): array
    {
        preg_match_all('/\{(\??)(\w+?)(:(\(.+?\)))?\}/', $pattern, $matches, PREG_SET_ORDER);
        
        return array_map(function ($match) {
            $parameter = new ParameterDefinition($match[2], '([^\/]+?)');
            
            if (isset($match[4])) {
                $parameter->pattern = $match[4];
            }
            
            if ($match[1] === '?') {
                $parameter->optional = true;
            }
            
            return $parameter;
        }, $matches);
    }
    
    /**
     * Build the matching regex
     *
     * @return string
     */
    private function buildRegex(): string
    {
        $regex = '/^' . str_ireplace('/', '\\/', $this->pattern) . '$/';
        $parameters = $this->parseParameters($this->pattern);
        
        array_walk($parameters, function (ParameterDefinition $parameter) use (&$regex) {
            if ($parameter->optional) {
                $match = '/\\\\\\/\{\?' . $parameter->name . '(:(\(.+?\)))?\}/'; // Five backshashes to match a single backslash, really????? At least it works that way... right?
                $regex = preg_replace($match, '(\/' . $parameter->pattern . ')?', $regex);
            } else {
                $regex = preg_replace('/\{' . $parameter->name . '(:(\(.+?\)))?\}/', $parameter->pattern, $regex);
            }
        });
        
        return $regex;
    }
    
    /**
     * Verify if the request matches the route
     *
     * @param ServerRequestInterface $request
     *
     * @return boolean TRUE if the the request string and the method match the route
     */
    public function match(ServerRequestInterface $request): bool
    {
        $requestString = $request->getUri()->getPath();
        $requestMethod = $request->getMethod();
        
        $methods = $this->methods;
        
        if (count($methods) === 0) {
           $methods[] = $requestMethod;
        }
        
        return (
            in_array($requestMethod, $this->methods, true) &&
            (preg_match($this->regex, $requestString) === 1)
        );
    }
    
    /**
     * Extract the parameters from the request string
     *
     * @param string $requestString
     *
     * @return array
     */
    private function extractArguments(string $requestString): array
    {
        $parameters = $this->parseParameters($this->pattern);
        preg_match_all($this->regex, $requestString, $regexValues, PREG_UNMATCHED_AS_NULL|PREG_SET_ORDER);
        $regexValues = $regexValues[0];
    
        array_shift($regexValues);
    
        $queryArguments = [];
    
        foreach ($parameters as $parameter) {
            foreach ($regexValues as $index => $value) {
                if (preg_match('#^' . $parameter->pattern . '$#', $value)) {
                    $queryArguments[$parameter->name] = $value;
                    unset($regexValues[$index]);
                    break;
                }
            }
        }
        
        return $queryArguments;
    }
    
    /**
     * Execute the handler
     *
     * @param ServerRequestInterface  $request
     * @param ContainerInterface|null $container
     *
     * @return ResponseInterface
     * @throws Exception
     */
    public function invoke(ServerRequestInterface $request, ContainerInterface $container = null): ResponseInterface
    {
        $requestString = $request->getUri()->getPath();
        $hasController = false;
        $resolver = new DependencyResolver($container);
        
        $queryArguments = $this->extractArguments($requestString);
        
        if (is_callable($this->callable)) {
            $reflectionMethod = new ReflectionFunction($this->callable);
        } else if (is_string($this->callable)) {
            if (strpos($this->callable, '@')) {
                [$controllerName, $method] = explode('@', $this->callable);
        
                if (class_exists($controllerName) && method_exists($controllerName, $method)) {
                    $reflectionClass = new ReflectionClass($controllerName);
                    $constructor = $reflectionClass->getConstructor();
                    $constructorArguments = [];
                    
                    if ($constructor !== null) {
                        foreach ($constructor->getParameters() as $parameter) {
                            $constructorArguments[$parameter->getName()] = $resolver->resolve($parameter);
                        }
                    }
    
                    $controller = $reflectionClass->newInstanceArgs($constructorArguments);
                    $reflectionMethod = new ReflectionMethod($controller, $method);
                    $hasController = true;
                } else {
                    throw new RuntimeException(sprintf('Method %s::%s not found', $controllerName, $method));
                }
            } else {
                $reflectionMethod = new ReflectionFunction($this->callable);
            }
        } else {
            throw new RuntimeException('The callable must be an instance of Closure or a reference to a controller class');
        }
        
        $methodArguments = [];
        
        foreach ($reflectionMethod->getParameters() as $parameter) {
            $methodArguments[$parameter->getName()] = $resolver->resolve($parameter, $queryArguments);
        }
    
        if ($hasController && $controller !== null) {
            $response = $reflectionMethod->invokeArgs($controller, $methodArguments);
        } else {
            $response = $reflectionMethod->invokeArgs($methodArguments);
        }
    
        if (!($response instanceof ResponseInterface)) {
            if ($this->callable instanceof Closure) {
                $name = 'Closure';
            } else {
                $name = $this->callable;
            }
            
            throw new RuntimeException(sprintf('%s must return an instance of %s', $name, ResponseInterface::class));
        }
        
        return $response;
    }
}