DistRoute
=========

A lightweight regular expression based request router with support for dependency injection targeted at projects consuming the recommendations of the [PHP-FIG](https://www.php-fig.org/psr/).

### Main Features

 - Named parameters in patterns
 - Optional parameters
 - Subrouting
 - Support for `GET`, `POST`, `DELETE`, `PUT`, `OPTIONS`, `HEAD`, `TRACE`, and custom request methods
 - Dependency injection
 - Full compatibility with [PSR-7](https://www.php-fig.org/psr/psr-7/) and [PSR-11](https://www.php-fig.org/psr/psr-11/)

Installation
------------

Installation is made with composer

```sh
composer require apinephp/dist-route
```

The package requires PHP 7.2 or newer.

Usage Example
-------------

```php
<?php
    
require '/path/to/vendor/autoload.php';

use Apine\DistRoute\Router;

$router = new Router();
$router->map(['GET'], '/users', UserController::class . '@all');
$router->get('/user/{id:(\d+)}', UserController::class . '@one');

$response = $router->handle($serverRequest);
```

Define a route
----------

Routes are defined by calling the mapping methods of the router like `map()` :

```php
$router->map($methods, $pattern, $handler);
```

The `$methods` is a list of one or many uppercase HTTP methods for which a route should match.

The `$pattern` defines a placeholder for matching using the syntax `{name}` which creates a placeholder named `name`. It is possible to change the regex pattern which a placeholder matches by writing the regex after the name and a colon. It is also possible to mark a placeholder as optional by adding a question mark (`?`) before its name.   

```php
$router->map(['GET'], '/user/{id}', 'handler');
$router->map(['GET'], '/user/{id:(\d+)}', 'handler');
$router->map(['GET'], '/user/{?id}', 'handler');
```

The `$handler` parameter is the handler that should be executed if the route matches a ServerRequest. It may be a `Closure`, the name of a function, or the fully qualified name of a class and the name of a method separated by a `@`. The handler must return a `ResponseInterface`.

### Shorthand methods

The router has shorthand methods for the following request methods : `GET`, `POST`, `DELETE`, `PUT`, `OPTIONS`, `HEAD`, and `TRACE`.

### Prefix Routes

The `group()` method allows to define multiple routes under a common prefix pattern. The `$pattern` parameter is a pattern as define above that will be prefixed to the child routes. The `$closure` parameter is a function called within the context of the router. It must receive as its only parameter an instance of `RouterInterface` which represents the current router.

The following will have the same effect as the example above:

 ```php
$router->group('/user', function (RouterInterface $mapper) {
 $mapper->map(['GET'], '/{id}', 'handler');
 $mapper->map(['GET'], '/{id:(\d+)}', 'handler');
 $mapper->map(['GET'], '/{?id}', 'handler');
});
 ```

Dispatching (handling) a request
--------------------------------

A request is handled by calling the `handle()` method of the router. The method accepts an instance of `ServerRequestInterface`.

The method throws an `RouteNotFoundException` if none of the routes matched the request. Otherwise, it returns the instance of `ResponseInterface` from the handler.