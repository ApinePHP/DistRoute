<?php
/**
 * RouteDependencyInjectorTest
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */
declare(strict_types=1);


use Apine\DistRoute\Route;
use Apine\DistRoute\RouteDependencyInjector;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class RouteDependencyInjectorTest extends TestCase
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
        
        $container = $this->getMockForAbstractClass(ContainerInterface::class);
        
        $route = $this->getMockBuilder(Route::class)
            ->setConstructorArgs([
                'methods' => ['GET'],
                'pattern' => '/test/{id:([0-9]+)}/{name:([A-Z]{2})}',
                'callable' => ResolverTestController::class . '@inputTestTwo'
            ])
            ->getMock();
        
        $resolver = new RouteDependencyInjector($container, $request);
        $depedencies = $resolver->resolve($route);
    }
}

class ResolverTestController {
    public function inputTest(int $input){}
    public function inputTestTwo(string $first, int $second){}
    public function inputTestThree(string $first, int $second = 2){}
}
