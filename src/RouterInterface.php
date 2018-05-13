<?php
/**
 * Interface for routers
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */

namespace Apine\DistRoute;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface RouterInterface
 *
 * @author Tommy Teasdale <tteasdaleroads@gmail.com>
 * @package Apine\Routing
 */
interface RouterInterface
{
    /**
     * Find the best matching route for the request
     *
     * @param Request $request
     *
     * @return Route
     */
    public function find(Request $request) : Route;
    
    /**
     * Dispatch a request
     *
     * @param Request $request
     *
     * @return ResponseInterface
     */
    public function dispatch(Request $request) : ResponseInterface;
}