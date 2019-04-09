# LOG engine monolog handler

PSR-3 Logging package to send log messages and exceptions to LOG engine service.

- **Author:** Valerio Barbera - [support@logengine.dev](mailto:support@logengine.dev)
- **Author Website:** [www.logengine.dev](https://www.logengine.dev)


# Installation
Install the latest version with `composer require log-engine/logger-php`

# Use in your application

There are two different transport options that can be configured to send data to LOG engine. Below will show how to implement the different transport options.

## Async transport

AsyncTransport is the most performant option to log versus LOG-Engine service. It collects log entries in batches, calls curl using the `exec` function, and sends data to the background immediately [`exec('curl ... &')`]. This will affect the performance of your application minimally, but it requires permissions to call `exec` inside the PHP script and it may cause silent data loss in the event of any network issues. 

This transport method does not work on Windows. 

```php
use LogEngine\Transport\AsyncTransport;
use LogEngine\Logger;

$transport = new AsyncTransport('LOGENGINE_URL', 'API_KEY', 'production');
$logger = new Logger($transport);

// Start logging
$logger->info('Track your application behaviour');
```

### Options

**Proxy**

AsyncTransport supports data delivery through proxy. Specify proxy using [libcurl format](http://curl.haxx.se/libcurl/c/CURLOPT_PROXY.html): <[protocol://][user:password@]proxyhost[:port]>

```php
$transport = new AsyncTransport(
    'LOGENGINE_URL', 
    'API_KEY', 
    'production', 
    ['proxy' => 'https://55.88.22.11:3128']
);
```

**Curl path**

It can be useful to specify `curl` destination path for ExecTransport. This option is set to 'curl' by default.

```php
$transport = new AsyncTransport(
    'LOGENGINE_URL', 
    'API_KEY', 
    'production', 
    ['curlPath' => '/usr/bin/curl']
);
```

## Curl transport

CurlTransport collects log entries in a single batch and sends data using native [PHP cURL](http://php.net/manual/en/book.curl.php) functions. 

```php
use LogEngine\Transport\CurlTransport;
use LogEngine\Logger;

$transport = new CurlTransport('LOGENGINE_URL', 'API_KEY', 'production');
$logger = new Logger($transport);
```

### Options

**Proxy**

CurlTransport supports data delivery through proxy. Specify proxy using [libcurl format](http://curl.haxx.se/libcurl/c/CURLOPT_PROXY.html): <[protocol://][user:password@]proxyhost[:port]>

```php
$transport = new CurlTransport(
    'LOGENGINE_URL', 
    'API_KEY', 
    'production', 
    ['proxy' => 'https://55.88.22.11:3128']
);
```

# Trableshooting

If transport does not work, try looking into `vendor\log-engine\logger-php\src\debug\log.log` file (if it is available for writing). Errors are also written to global PHP [error_log](http://php.net/manual/en/errorfunc.configuration.php#ini.error-log). Note that AsyncTransport does not produce any errors at all because it is executed in the backgraound, but you can switch it to debug mode to investigate if needed:

```php
$transport = new AsyncTransport(
    'LOGENGINE_URL', 
    'API_KEY', 
    'production', 
    ['debug' => true]
);
```

# Log an exception

LOG Engine logger give you the ability to send exceptions to the platform for better investigation and reporting.

You can call dedicated method inside the logger:

```php
try {
    // Your dangerous code here
    throw new UnauthorizedException("You don't have permission to access.");
    
} catch (UnauthorizedException $exception) {
    $logger->logException($exception);
}
```

## LICENSE

This package are licensed under the [MIT](LICENSE) license.
