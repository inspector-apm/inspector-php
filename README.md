# LOG Engine for php

PSR-3 compliant package to send log messages and exceptions to LOG Engine service.

- **Author:** Valerio Barbera - [support@logengine.dev](mailto:support@logengine.dev)
- **Author Website:** [www.logengine.dev](https://www.logengine.dev)


# Installation
Install the latest version with `composer require logengine/logengine-php`

# Use in your application

```php
<?php
require __DIR__ . '/../vendor/autoload.php';
use LogEngine\LogEngine;

$logengine = new LogEngine('https://www.logengine.dev/api', 'API_KEY');

// Start logging
try {

    $logengine->info('Track your app behaviour');
    throw new UnauthorizedException("You don't have permission to access.");

} catch(UnauthorizedException $exception) {
    $logengine->logException($exception);
}
```

### Options

**Proxy**

LOG Engine library supports data delivery through proxy. Specify proxy using [libcurl format](http://curl.haxx.se/libcurl/c/CURLOPT_PROXY.html): <[protocol://][user:password@]proxyhost[:port]>

```php
$logengine = new LogEngine\LogEngine(
    'https://www.logengine.dev/api', 
    'API_KEY', 
    ['proxy' => 'https://55.88.22.11:3128']
);
```

**Curl path**

It can be useful to specify `curl` destination path for AsyncTransport. This option is set to 'curl' by default.

```php
$logengine = new LogEngine\LogEngine(
    'https://www.logengine.dev/api', 
    'API_KEY', 
    ['curlPath' => '/usr/bin/curl']
);
```

# Log Exceptions

LOG Engine give you the ability to send exceptions to the platform for better investigation and reporting.

You can use `logException` method inside the `LogEngine` service class:

```php
try {
    // Your dangerous code here
    throw new UnauthorizedException("You don't have permission to access.");
    
} catch (UnauthorizedException $exception) {
    $logengine->logException($exception);
}
```

## LICENSE

This package are licensed under the [MIT](LICENSE) license.
