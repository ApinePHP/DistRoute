<?php
/**
 * DependencyResolverTest
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection PhpUnusedLocalVariableInspection */

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Apine\DistRoute\DependencyResolver;
use Apine\DistRoute\Route;
use PHPUnit\Framework\TestCase;

class DependencyResolverTest extends TestCase
{
    public function testResolve()
    {
        $uri = $this->getMockBuilder(UriInterface::class)
            ->setMethods(['getPath'])
            ->getMockForAbstractClass();
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getUri'])
            ->getMockForAbstractClass();
        $uri->method('getPath')->willReturn('/test/15/AS');
        $request->method('getUri')->willReturn($uri);
        
        $route = $this->getMockBuilder(Route::class)
            ->setConstructorArgs([
                'methods' => ['GET'],
                'pattern' => '/test/{id:([0-9]+)}/{name:([A-Z]{2})}',
                'callable' => DependencyResolverTestController::class . '@inputTestTwo'
            ])
            ->getMock();
        
        $resolver = new DependencyResolver();
        
        $method = new ReflectionMethod(DependencyResolverTestController::class, 'inputTestTwo');
        $parameter = $method->getParameters()[0];
        $value = $resolver->resolve($parameter, [
            'id' => 15,
            'name' => 'AS'
        ]);
        $this->assertEquals('AS', $value);
    }
    
    public function testResolveContainerService()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)
            ->setMethods(['get', 'has'])
            ->getMockForAbstractClass();
        $container->expects($this->any())->method('has')->with($this->equalTo(ResponseInterface::class))->will($this->returnValue(true));
        $container->expects($this->any())->method('get')->with($this->equalTo(ResponseInterface::class))->willReturnCallback(function () {
            return $this->getMockForAbstractClass(ResponseInterface::class);
        });
    
        $resolver = new DependencyResolver($container);
    
        $method = new ReflectionMethod(DependencyResolverTestController::class, 'inputTestTwo');
        $parameter = $method->getParameters()[2];
        $value = $resolver->resolve($parameter, [
            'id' => 15,
            'name' => 'AS'
        ]);
        $this->assertInstanceOf(ResponseInterface::class, $value);
    }
    
    public function testResolveContainerServiceNotFound()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)
            ->setMethods(['get', 'has'])
            ->getMockForAbstractClass();
        $container->expects($this->any())->method('has')->with($this->equalTo(ServerRequestInterface::class))->will($this->returnValue(false));
        
        $resolver = new DependencyResolver($container);
        
        $class = new ReflectionClass(DependencyResolverTestController::class);
        $constructor = $class->getConstructor();
        
        $parameter = $constructor->getParameters()[0];
        $value = $resolver->resolve($parameter, [
            'id' => 15,
            'name' => 'AS'
        ]);
        $this->assertNull($value);
    }
    
    public function testResolveValueNotFound()
    {
        $resolver = new DependencyResolver();
    
        $method = new ReflectionMethod(DependencyResolverTestController::class, 'inputTestTwo');
        $parameter = $method->getParameters()[0];
        $value = $resolver->resolve($parameter, [
            'id' => 15
        ]);
        $this->assertNull($value);
    }
    
    public function testResolveValueNotFoundDefaultValue()
    {
        $resolver = new DependencyResolver();
        
        $method = new ReflectionMethod(DependencyResolverTestController::class, 'inputTestTwo');
        $parameter = $method->getParameters()[3];
        $value = $resolver->resolve($parameter, [
            'id' => 15
        ]);
        $this->assertEquals('Merlin', $value);
    }
}

class DependencyResolverTestController {
    public function __construct(ServerRequestInterface $request) {}
    public function inputTest(int $input){}
    public function inputTestTwo(string $name, int $id, ResponseInterface $request, $cat = 'Merlin'){}
}