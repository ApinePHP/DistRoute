DistRoute
=========

A regular expression based request router

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

Usage
-----

```php
<?php
    
require '/path/to/vendor/autoload.php';

use Apine\DistRoute\Router;

$router = new Router();
$router->map(['GET'], '/users', UserController::class, 'all');

$response = $router->dispatch($serverRequest);
```
