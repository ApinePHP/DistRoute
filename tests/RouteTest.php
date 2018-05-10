<?php
/**
 * RouteTest
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */

/** @noinspection PhpParamsInspection */
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnusedLocalVariableInspection */

declare(strict_types=1);

use Apine\DistRoute\ParameterDefinition;
use Apine\DistRoute\Route;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;

class RouteTest extends TestCase
{
    public function testParameterDefinition()
    {
        $methods = ["GET"];
        $pattern = "/test/{input:([0-9]+)}";
        $controller = TestController::class;
        $action = "inputTest";
    
        $route = new Route($methods, $pattern, $controller . '@' . $action);
        $parameters = $route->parseParameters($route->pattern);
        
        $this->assertEquals(
            [
                new ParameterDefinition('input', '([0-9]+)')
            ],
            $parameters
        );
    }
    
    /**
     * @depends testParameterDefinition
     */
    public function testConstructor()
    {
        $methods = ["GET"];
        $pattern = "/test/{input}";
        $controller = TestController::class;
        $action = "inputTest";
        $callable = $controller . '@' . $action;
        
        $route = new Route($methods, $pattern, $callable);
        
        $this->assertAttributeEquals($methods, 'methods', $route);
        $this->assertAttributeEquals($pattern, 'pattern', $route);
        $this->assertAttributeEquals($callable, 'callable', $route);
        $this->assertAttributeNotEmpty('regex', $route);
    }
    
    public function testParseOptionalParameter()
    {
        $methods = ["GET"];
        $pattern = "/test/{?input}";
        $controller = TestController::class;
        $action = "inputTest";
        $callable = $controller . '@' . $action;
        
        $route = new Route($methods, $pattern, $callable);
        $parameters = $route->parseParameters($route->pattern);
        
        $parameter = $parameters[0];
        $this->assertTrue($parameter->optional);
    }
    
    public function testMatch()
    {
        $routeOne = new Route(
            ['GET'],
            '/{input}',
            TestController::class . '@inputTest'
        );
        $routeTwo = new Route(
            ['GET'],
            '/{input:([0-9]+)}',
            TestController::class . '@inputTest'
        );
        $routeThree = new Route(
            ['POST'],
            '/test/{first}/{second}',
            TestController::class . '@inputTestTwo'
        );
        $routeFour = new Route(
            ['POST'],
            '/test/{first:(\w+)}/{second:([0-9]+)}',
            TestController::class . '@inputTestTwo'
        );
        
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getMethod', 'getUri'])
            ->getMockForAbstractClass();
        
        $uri = $this->getMockBuilder(UriInterface::class)
            ->setMethods(['getPath'])
            ->getMockForAbstractClass();
        
        $uri->method('getPath')->willReturn('/15');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
        
        $this->assertTrue($routeOne->match($request));
        /*$this->assertFalse($routeOne->match('/','GET'));
        $this->assertTrue($routeOne->match('/as','GET'));
        $this->assertFalse($routeOne->match('/15','POST'));*/
    
        $this->assertTrue($routeTwo->match($request));
        /*$this->assertFalse($routeTwo->match('/','GET'));
        $this->assertFalse($routeTwo->match('/as','GET'));
        $this->assertFalse($routeTwo->match('/15','POST'));*/
        
        /*$this->assertTrue($routeThree->match('/test/as/15', 'POST'));
        $this->assertFalse($routeThree->match('/test/as/15', 'GET'));
        $this->assertFalse($routeThree->match('/test/15', 'POST'));
        $this->assertFalse($routeThree->match('/test', 'POST'));
        $this->assertFalse($routeThree->match('/', 'POST'));*/
        $this->assertFalse($routeThree->match($request));
    
        /*$this->assertTrue($routeFour->match('/test/as/15', 'POST'));
        $this->assertFalse($routeFour->match('/test/テスト/15', 'POST'));
        $this->assertFalse($routeFour->match('/test/as/no', 'POST'));*/
    }
    
    public function testMatchDefinitionHasFewerParameterThanPathParts()
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getMethod', 'getUri'])
            ->getMockForAbstractClass();
    
        $uri = $this->getMockBuilder(UriInterface::class)
            ->setMethods(['getPath'])
            ->getMockForAbstractClass();
    
        $uri->method('getPath')->willReturn('/test/as/15');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
        
        $route = new Route(
            ['GET'],
            '/{input}',
            TestController::class . '@inputTest'
        );
        
        $this->assertFalse($route->match($request));
    }
    
    public function testMatchDefinitionHasOptionalParameter()
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getMethod', 'getUri'])
            ->getMockForAbstractClass();
    
        $uri = $this->getMockBuilder(UriInterface::class)
            ->setMethods(['getPath'])
            ->getMockForAbstractClass();
    
        $uri->method('getPath')->willReturn('/test/param/15');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
        
        $route = new Route(
            ['GET'],
            '/test/{first}/{?second}',
            TestController::class . '@inputTestTwo'
        );
        
        $this->assertTrue($route->match($request));
    
        $uri->method('getPath')->willReturn('/test/param');
        $request->method('getUri')->willReturn($uri);
        
        $this->assertTrue($route->match($request));
    }
    
    public function testInvoke()
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getMethod', 'getUri'])
            ->getMockForAbstractClass();
    
        $uri = $this->getMockBuilder(UriInterface::class)
            ->setMethods(['getPath'])
            ->getMockForAbstractClass();
    
        $uri->method('getPath')->willReturn('/test/param/15');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
        
        $response = $this->getMockForAbstractClass(ResponseInterface::class);
    
        $route = new Route(
            ['GET'],
            '/test/{first}/{?second}',
            function (string $first, int $second = 404) use ($response) {
                $result = $first.$second;
                return $response;
            }
        );
        
        $response = $route->invoke($request);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
    
    public function testInvokeWithDependencyInjection()
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getMethod', 'getUri'])
            ->getMockForAbstractClass();
    
        $uri = $this->getMockBuilder(UriInterface::class)
            ->setMethods(['getPath'])
            ->getMockForAbstractClass();
    
        $uri->method('getPath')->willReturn('/test/param');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
    
        $container = $this->getMockBuilder(ContainerInterface::class)
            ->setMethods(['get', 'has'])
            ->getMockForAbstractClass();
        $container->expects($this->any())->method('has')->with($this->equalTo(ResponseInterface::class))->will($this->returnValue(true));
        $container->expects($this->any())->method('get')->with($this->equalTo(ResponseInterface::class))->willReturnCallback(function () {
            return $this->getMockForAbstractClass(ResponseInterface::class);
        });
    
        $route = new Route(
            ['GET'],
            '/test/{first}/{?second}',
            function (ResponseInterface $response, string $first, int $second = 404) {
                $result = $first.$second;
                return $response;
            }
        );
    
        $response = $route->invoke($request, $container);
    
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
    
    public function testInvokeControllerClass()
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getMethod', 'getUri'])
            ->getMockForAbstractClass();
        
        $uri = $this->getMockBuilder(UriInterface::class)
            ->setMethods(['getPath'])
            ->getMockForAbstractClass();
        
        $uri->method('getPath')->willReturn('/test/param/15');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
    
        $container = $this->getMockBuilder(ContainerInterface::class)
            ->setMethods(['get', 'has'])
            ->getMockForAbstractClass();
        $container->expects($this->any())->method('has')->with($this->equalTo(ResponseInterface::class))->will($this->returnValue(true));
        $container->expects($this->any())->method('get')->with($this->equalTo(ResponseInterface::class))->willReturnCallback(function () {
            return $this->getMockForAbstractClass(ResponseInterface::class);
        });
        
        $route = new Route(
            ['GET'],
            '/test/{first}/{?second}',
            TestController::class . '@inputTestThree'
        );
        
        $response = $route->invoke($request, $container);
        
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
    
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /(.+?) must return an instance of (.+?)/
     */
    public function testInvokeDoesNotReturnResponse()
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getMethod', 'getUri'])
            ->getMockForAbstractClass();
    
        $uri = $this->getMockBuilder(UriInterface::class)
            ->setMethods(['getPath'])
            ->getMockForAbstractClass();
    
        $uri->method('getPath')->willReturn('/test/param/15');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
    
        $route = new Route(
            ['GET'],
            '/test/{first}/{?second}',
            function (string $first, int $second = 404) {
                $result = $first.$second;
            }
        );
    
        $response = $route->invoke($request);
    }
}

class TestController {
    public function inputTest(int $input){}
    public function inputTestTwo(string $first, int $second){}
    public function inputTestThree(ResponseInterface $response, string $first, int $second = 2){
        return $response;
    }
}