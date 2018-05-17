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

Add this repository to your composer config:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/ApinePHP/DistRoute"
    }
  ]
}
```

Then install the package with composer

```sh
composer require apine/dist-route
```

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
