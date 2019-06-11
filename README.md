# Inspector

[![Build Status](https://travis-ci.org/inspector-apm/inspector-php.svg?branch=master)](https://travis-ci.org/inspector-apm/inspector-php)
[![Latest Stable Version](https://poser.pugx.org/log-engine/logengine-php/v/stable)](https://packagist.org/packages/inspector-apm/inspector-php)

Inspector is a composer package to add Real-Time performance monitoring to your app. 

![](<https://app.inspector.dev/images/frontend/demo.gif>)

Install the latest version by:

```shell
composer require inspector-apm/inspector-php
```

It allows engineers to collect, tail and search their application events in real time 
with one simple and easy to use web interface, even if the application or server is down.

## Use

To connect your app with Inspector you need to provide the API KEY when create a `Configuration` instance:

```php
<?php
require __DIR__ . '/../vendor/autoload.php';

use Inspector\Inspector;
use Inspector\Configuration;

$configuration = new Configuration('API_KEY');
$inspector = new Inspector($configuration);
```

All start with a `transaction`. Transaction represent an execution cycle and it can contains one or hundred of events:

```php
// Start execution cycle with a transaction
$inspector->startTransaction($_SERVER['PATH_INFO']);

// Trace performance of code blocks
$span = $inspector->startSpan('Process');
try {

    throw new UnauthorizedException("You don't have permission to access.");

} catch(UnauthorizedException $exception) {
    $apm->reportException($exception);
} fianlly {
    $span->end();
}
```

Inspector will collect many useful information to produce performance chart in the dashboard.

**[See official documentation](https://app.inspector.dev/docs/2.0/platforms/php)**

## LICENSE

This package are licensed under the [MIT](LICENSE) license.
