# Inspector | Code Execution Monitoring Tool

[![Total Downloads](https://poser.pugx.org/inspector-apm/inspector-php/downloads)](//packagist.org/packages/inspector-apm/inspector-php)
[![Latest Stable Version](https://poser.pugx.org/inspector-apm/inspector-php/v/stable)](https://packagist.org/packages/inspector-apm/inspector-php)
[![License](https://poser.pugx.org/inspector-apm/inspector-php/license)](//packagist.org/packages/inspector-apm/inspector-php)

> Before moving on, please consider giving us a GitHub star ⭐️. Thank you!

Code Execution Monitoring, built for PHP developers.

## Requirements

- PHP >= ^8.1

## Install
Install the latest version by:

```shell
composer require inspector-apm/inspector-php
```

## Use

To start sending data to Inspector you need an Ingestion Key to create an instance of the `Configuration` class.
You can obtain `INSPECTOR_API_KEY` creating a new project in your [Inspector](https://www.inspector.dev) dashboard.

```php
use Inspector\Inspector;
use Inspector\Configuration;

$configuration = new Configuration('YOUR_INGESTION_KEY');
$inspector = new Inspector($configuration);
```

All start with a `transaction`. Transaction represents an execution cycle, and it can contain one or hundred of segments:

```php
// Start an execution cycle with a transaction
$inspector->startTransaction($_SERVER['PATH_INFO']);
```

Use `addSegment` method to monitor a code block in your transaction:

```php
$result = $inspector->addSegment(function ($segment) {
    // Do something here...
	return "Hello World!";
}, 'my-process');

echo $result; // this will print "Hello World!"
```

Inspector will monitor your code execution in real time alerting you if something goes wrong.

## Custom Transport
You can also set up a custom transport class to transfer monitoring data from your server to Inspector
in a personalized way.

The transport class needs to implement `\Inspector\Transports\TransportInterface`:

```php
class CustomTransport implements \Inspector\Transports\TransportInterface
{
    protected $configuration;

    protected $queue = [];

    public function __constructor($configuration)
    {
        $this->configuration = $configuration;
    }

    public function addEntry(\Inspector\Models\Model $entry)
    {
        // Add an \Inspector\Models\Model entry in the queue.
        $this->queue[] = $entry;
    }

    public function flush()
    {
        // Performs data transfer.
        $handle = curl_init('https://ingest.inspector.dev');
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_HTTPHEADER, [
            'X-Inspector-Key: xxxxxxxxxxxx',
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($this->queue));
        curl_exec($handle);
        curl_close($handle);
    }
}
```

Then you can set the new transport in the `Inspector` instance
using a callback that will receive the current configuration state as parameter.

```php
$inspector->setTransport(function (\Inspector\Configuration $configuration) {
    return new CustomTransport($configuration);
});
```

**[Chek out the official documentation](https://docs.inspector.dev/php)**

## Contributing

We encourage you to contribute to Inspector! Please check out the [Contribution Guidelines](CONTRIBUTING.md) about how to proceed. Join us!

## LICENSE

This package is licensed under the [MIT](LICENSE) license.
