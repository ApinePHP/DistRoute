<?php
/**
 * DependencyInjector
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);

namespace Apine\DistRoute;

use Psr\Container\ContainerInterface;
use ReflectionParameter;

/**
 * Class DependencyInjector
 *
 * @package Apine\DistRoute
 */
class DependencyResolver
{
    private $container;
    
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    
    /**
     * @param ReflectionParameter $parameter
     * @param array               $arguments
     *
     * @return mixed
     */
    public function resolve(ReflectionParameter $parameter, array $arguments = [])
    {
        $name = $parameter->getName();
        $type = $parameter->getType();
    
        if (isset($arguments[$name])) {
            if ($type !== null && !$type->isBuiltin()) {
                $class = (string)$type;
                $value = new $class($arguments[$name]);
            } else {
                $value = $arguments[$name];
            }
        } else {
            $value = null;
    
            if ($this->container instanceof ContainerInterface && !$type->isBuiltin()) {
                if ($this->container->has((string)$type)) {
                    $value = $this->container->get((string)$type);
                }
            }
            
            if ($value === null && $parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            }
        }
        
        return $value;
    }
}