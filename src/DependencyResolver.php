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
use ReflectionType;

/**
 * This class resolve parameters into their corresponding value
 *
 * @package Apine\DistRoute
 */
class DependencyResolver
{
    /**
     * @var ContainerInterface|null
     */
    private $container;
    
    /**
     * @param ContainerInterface|null $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
    
    /**
     * Compare a method parameter with a list of request arguments and
     * a container to resolve it into its value/dependency
     *
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
            if (self::isReflectionType($type)) {
                $class = (string)$type;
                $value = new $class($arguments[$name]);
            } else {
                $value = $arguments[$name];
            }
        } else {
            $value = null;
    
            if (
                $this->container instanceof ContainerInterface &&
                self::isReflectionType($type) &&
                $this->container->has($name)
            ) {
                $value = $this->container->get($name);
                $class = (string)$type;
                
                if (!($value instanceof $class)) {
                    $value = null;
                }
            }
            
            if ($value === null && $parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            }
        }
        
        return $value;
    }
    
    /**
     * @param mixed $type
     *
     * @return bool
     */
    private static function isReflectionType($type): bool
    {
        return (
            $type instanceof ReflectionType &&
            !$type->isBuiltin()
        );
    }
}