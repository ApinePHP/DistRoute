<?php
/**
 * Route
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

namespace Apine\DistRoute;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function in_array, array_map, array_walk;
use function str_ireplace, preg_match_all, preg_replace;

/**
 * Class Route
 *
 * @package Apine\DistRoute
 */
class Route
{
    /**
     * @var string
     */
    public $pattern;
    
    /**
     * @var string[]
     */
    public $methods;
    
    /**
     * @var callable|string
     */
    private $callable;
    
    /**
     * @var string
     */
    public $regex;
    
    /**
     * Route constructor.
     *
     * @param string[] $methods
     * @param string $pattern
     * @param callable|string $callable
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
     * @param string $pattern
     *
     * @return array
     */
    public function parseParameters(string $pattern): array
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
     * @return string
     */
    private function buildRegex(): string
    {
        $regex = '/^' . str_ireplace('/', '\\/', $this->pattern) . '$/';
        $parameters = $this->parseParameters($this->pattern);
        
        array_walk($parameters, function (ParameterDefinition $parameter) use (&$regex) {
            if ($parameter->optional) {
                $match = '/\\\\\\/\{\?' . $parameter->name . '(:(\(.+?\)))?\}/'; // Five backshashes to match a single backslash, really????? At least it works that way... right?
                $regex = preg_replace($match, '(\/?' . $parameter->pattern . ')?', $regex);
            } else {
                $regex = preg_replace('/\{' . $parameter->name . '(:(\(.+?\)))?\}/', $parameter->pattern, $regex);
            }
        });
        
        return $regex;
    }
    
    /**
     * @param ServerRequestInterface $request
     *
     * @return boolean TRUE if the the request string and the method match the route
     */
    public function match(ServerRequestInterface $request): bool
    {
        $requestString = $request->getUri()->getPath();
        $requestMethod = $request->getMethod();
        
        return (
            in_array($requestMethod, $this->methods, true) &&
            (preg_match($this->regex, $requestString) === 1)
        );
    }
    
    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function invoke(ServerRequestInterface $request): ResponseInterface
    {
    
    }
}