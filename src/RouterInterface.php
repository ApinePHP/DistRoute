<?php
/**
 * RouteMappingInterface
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

namespace Apine\DistRoute;

use Closure;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Representation of a router
 *
 * A router is a HTTP request handler thus extends the request handler
 * interface as defined by the PHP-FIG wit the PSR-15 recommendation.
 *
 * @package Apine\DistRoute
 */
interface RouterInterface extends RequestHandlerInterface
{
    /**
     * Maps a new route to one or multiple request methods
     *
     * The array of accepted methods MUST be any standard or custom capitalized
     * method names as defined in RFC-7231. It must contain at least one valid
     * method name otherwise the route MAY not be matched.
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
     * @param string[]        $methods A list of accepted request methods
     * @param string          $pattern A route pattern similar to /user/{id:(\d+)}
     * @param callable|string $callable The handler to be executed
     */
    public function map(array $methods, string $pattern, $callable);
    
    /**
     * Adds many routes under a common prefix
     *
     * Adding routes by pre-pending the prefix pattern to their pattern.
     *
     * The closure SHOULD be called within a context that has access to a RouteMappingInterface.
     *
     * @param string   $pattern
     * @param Closure $closure
     */
    public function group(string $pattern, Closure $closure);
    
    /**
     * Map a new route to any standard or custom request method
     *
     * @see RouterInterface::map()
     *
     * @param string            $pattern
     * @param callable|string   $callable
     */
    public function any(string $pattern, $callable);
    
    /**
     * Map a new route to the GET request method
     *
     * @see RouterInterface::map()
     *
     * @param string            $pattern
     * @param callable|string   $callable
     */
    public function get(string $pattern, $callable);
    
    /**
     * Map a new route to the POST request method
     *
     * @see RouterInterface::map()
     *
     * @param string            $pattern
     * @param callable|string   $callable
     */
    public function post(string $pattern, $callable);
    
    /**
     * Map a new route to the PUT request method
     *
     * @see RouterInterface::map()
     *
     * @param string            $pattern
     * @param callable|string   $callable
     */
    public function put(string $pattern, $callable);
    
    /**
     * Map a new route to the DELETE request method
     *
     * @see RouterInterface::map()
     *
     * @param string            $pattern
     * @param callable|string   $callable
     */
    public function delete(string $pattern, $callable);
    
    /**
     * Map a new route to the HEAD request method
     *
     * @see RouterInterface::map()
     *
     * @param string            $pattern
     * @param callable|string   $callable
     */
    public function head(string $pattern, $callable);
    
    /**
     * Map a new route to the OPTIONS request method
     *
     * @see RouterInterface::map()
     *
     * @param string            $pattern
     * @param callable|string   $callable
     */
    public function options(string $pattern, $callable);
    
    /**
     * Map a new route to the TRACE request method
     *
     * @see RouterInterface::map()
     *
     * @param string            $pattern
     * @param callable|string   $callable
     */
    public function trace(string $pattern, $callable);
}