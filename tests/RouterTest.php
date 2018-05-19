<?php
/**
 * RouterTest
 *
 * @license MIT
 * @copyright 2018 Tommy Teasdale
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpParamsInspection */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUnusedLocalVariableInspection */
/** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection UnnecessaryAssertionInspection */
/** @noinspection PhpMethodParametersCountMismatchInspection */

declare(strict_types=1);

use Apine\DistRoute\Route;
use Apine\DistRoute\Router;
use Apine\DistRoute\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class RouterTest extends TestCase
{
    public function testConstructor()
    {
        $container = $this->getMockForAbstractClass(ContainerInterface::class);
        $router = new Router($container);
        
        $this->assertAttributeInstanceOf(ContainerInterface::class, 'container', $router);
        $this->assertAttributeEmpty('routes', $router);
        $this->assertAttributeEmpty('basePattern', $router);
    }
    
    public function testSetBasePattern()
    {
        $router = new Router();
        $router->setBasePattern('/test');
        
        $this->assertAttributeEquals('/test', 'basePattern', $router);
    }
    
    public function testGetBasePattern()
    {
        $router = new Router();
        $router->setBasePattern('/test');
    
        $this->assertEquals('/test', $router->getBasePattern());
    }
    
    public function testMap()
    {
        $router = new Router();
        $this->assertAttributeEmpty('routes', $router);
        
        $route = $router->map(['GET', 'DELETE'], '/test/{number}', function(){});
        $this->assertAttributeNotEmpty('routes', $router);
        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeEquals(['GET', 'DELETE'], 'methods', $route);
    }
    
    public function testGet()
    {
        $router = new Router();
        $route = $router->get('/test/{number}', function(){});
        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeEquals(['GET'], 'methods', $route);
    }
    
    public function testPost()
    {
        $router = new Router();
        $route = $router->post('/test/{number}', function(){});
        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeEquals(['POST'], 'methods', $route);
    }
    
    public function testDelete()
    {
        $router = new Router();
        $route = $router->delete('/test/{number}', function(){});
        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeEquals(['DELETE'], 'methods', $route);
    }
    
    public function testPut()
    {
        $router = new Router();
        $route = $router->put('/test/{number}', function(){});
        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeEquals(['PUT'], 'methods', $route);
    }
    
    public function testTrace()
    {
        $router = new Router();
        $route = $router->trace('/test/{number}', function(){});
        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeEquals(['TRACE'], 'methods', $route);
    }
    
    public function testOptions()
    {
        $router = new Router();
        $route = $router->options('/test/{number}', function(){});
        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeEquals(['OPTIONS'], 'methods', $route);
    }
    
    public function testHead()
    {
        $router = new Router();
        $route = $router->head('/test/{number}', function(){});
        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeEquals(['HEAD'], 'methods', $route);
    }
    
    public function testCustom()
    {
        $router = new Router();
        $route = $router->map(['PATCH'], '/test/{number}', function(){});
        $this->assertInstanceOf(Route::class, $route);
        $this->assertAttributeEquals(['PATCH'], 'methods', $route);
    }
    
    public function testAny()
    {
        $router = new Router();
        $route = $router->any('/test/{number}', function(){});
        $this->assertInstanceOf(Route::class, $route);
    }
    
    public function testGroup()
    {
        $router = new Router();
        $this->assertAttributeEmpty('basePattern', $router);
        $parent = $this;
        
        $router->group('/test', function ($mapper) use ($parent) {
            $parent->assertInstanceOf(RouterInterface::class, $mapper);
            $parent->assertAttributeNotEmpty('basePattern', $mapper);
            
            $mapper->any('/{number}', function(){});
        });
    
        $this->assertAttributeEmpty('basePattern', $router);
        $this->assertAttributeNotEmpty('routes', $router);
    }
    
    /**
     * @expectedException \Apine\DistRoute\RouteNotFoundException
     * @expectedExceptionMessageRegExp /Route for request (.+?) not found/
     */
    public function testHandleNoMatchFound()
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getMethod', 'getUri'])
            ->getMockForAbstractClass();
    
        $uri = $this->getMockBuilder(UriInterface::class)
            ->setMethods(['getPath'])
            ->getMockForAbstractClass();
    
        $uri->method('getPath')->willReturn('/test/1567/other');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
    
        $router = new Router();
        $router->get('/test/{number}', function(){});
        
        $response = $router->handle($request);
    }
    
    /**
     * @expectedException \RuntimeException
     */
    public function testHandleErrorExecution()
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->setMethods(['getMethod', 'getUri'])
            ->getMockForAbstractClass();
    
        $uri = $this->getMockBuilder(UriInterface::class)
            ->setMethods(['getPath'])
            ->getMockForAbstractClass();
    
        $uri->method('getPath')->willReturn('/test/1567');
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
    
        $router = new Router();
        $router->get('/test/{number}', function(){});
    
        $response = $router->handle($request);
    }
}
