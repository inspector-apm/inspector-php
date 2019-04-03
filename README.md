# LOG engine monolog handler

Logging package to send log messages and exceptions to LOG engine service.

- **Author:** Valerio Barbera - [support@logengine.dev](mailto:support@logengine.dev)
- **Author Website:** [www.logengine.dev]


# Installation
Install the latest version with `composer require log-engine/logger-php`

# Use in your application

There are three different transport options that can be configured to send data to LOG engine. Below will show how to implement the different transport options.

## Async transport

AsyncTransport is the most performant option to log versus LOG-Engine service. It collects log entries in batches, calls curl using the `exec` function, and sends data to the background immediately [`exec('curl ... &')`]. This will affect the performance of your application minimally, but it requires permissions to call `exec` inside the PHP script and it may cause silent data loss in the event of any network issues. 

This transport method does not work on Windows. To configure AsyncTransport you need to pass the <u>API KEY</u> generated in your LOG engine administration console and environment name of your app installation:

```php
use LogEngine\Transport\ExecTransport;
use LogEngine\Standalone\Logger;
    
$transport = new AsyncTransport('your_project_key', 'production');
$logger = new Logger($transport);
```

### Options

**Proxy**

AsyncTransport supports data delivery through proxy. Specify proxy using [libcurl format](http://curl.haxx.se/libcurl/c/CURLOPT_PROXY.html): <[protocol://][user:password@]proxyhost[:port]>

```php
$transport = new AsyncTransport($apiKey, 'production', ['proxy' => 'https://55.88.22.11:3128']);
```

**Curl path**

It can be useful to specify `curl` destination path for ExecTransport. This option is set to 'curl' by default.

```php
$transport = new AsyncTransport($apiKey, 'production', ['curlPath' => '/usr/bin/curl']);
```

## Curl transport

CurlTransport does not require a Stackify agent to be installed and it also sends data directly to Stackify services. It collects log entries in a single batch and sends data using native [PHP cURL](http://php.net/manual/en/book.curl.php) functions. This way is a blocking one, so it should not be used on production environments. To configure CurlTransport you need to pass the <u>API KEY</u> generated in your LOG engine administration console and environment name of your app installation:

```php
use LogEngine\Transport\CurlTransport;
use LogEngine\Standalone\Logger;
    
$transport = new CurlTransport('your_key', 'production');
$logger = new Logger($transport);
```

### Options

**Proxy**

CurlTransport supports data delivery through proxy. Specify proxy using [libcurl format](http://curl.haxx.se/libcurl/c/CURLOPT_PROXY.html): <[protocol://][user:password@]proxyhost[:port]>

```php
$transport = new CurlTransport($apiKey, 'production', ['proxy' => 'https://55.88.22.11:3128']);
```

# Trableshooting

If transport does not work, try looking into `vendor\logengine\logger\src\debug\log.log` file (if it is available for writing). Errors are also written to global PHP [error_log](http://php.net/manual/en/errorfunc.configuration.php#ini.error-log). Note that AsyncTransport does not produce any errors at all because it is executed in the backgraound, but you can switch it to debug mode:

```php
$transport = new AsyncTransport($apiKey, 'development', ['debug' => true]);
```

## LICENSE

This package are licensed under the [MIT](LICENSE) license.
