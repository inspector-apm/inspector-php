<?php

declare(strict_types=1);

namespace Inspector\Transports;

use Inspector\Configuration;
use Inspector\Exceptions\InspectorException;

use function array_key_exists;
use function curl_close;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt;
use function date;
use function error_log;
use function function_exists;
use function get_class;

use const CURLINFO_HTTP_CODE;
use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_PROXY;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;

class CurlTransport extends AbstractApiTransport
{
    /**
     * CurlTransport constructor.
     *
     * @param Configuration $configuration
     * @throws InspectorException
     */
    public function __construct(Configuration $configuration)
    {
        // System need to have CURL available
        if (!function_exists('curl_init')) {
            throw new InspectorException('cURL PHP extension is not available');
        }

        parent::__construct($configuration);
    }

    /**
     * Deliver items to Inspector.
     *
     * @param string $data
     */
    public function sendChunk(string $data): void
    {
        $headers = [];

        foreach ($this->getApiHeaders() as $name => $value) {
            $headers[] = "$name: $value";
        }

        $handle = curl_init($this->config->getUrl());

        curl_setopt($handle, CURLOPT_POST, true);

        // Tell cURL that it should only spend 10 seconds trying to connect to the URL in question.
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        // A given cURL operation should only take 30 seconds max.
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);

        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);

        if (array_key_exists('proxy', $this->config->getOptions())) {
            curl_setopt($handle, CURLOPT_PROXY, $this->config->getOptions()['proxy']);
        }

        $response = curl_exec($handle);
        $errorNo = curl_errno($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);

        // 200 OK
        // 403 Account has reached no. transactions limit
        if (0 !== $errorNo || (200 !== $code && 201 !== $code && 403 !== $code)) {
            error_log(date('Y-m-d H:i:s') . " - [Warning] [" . get_class($this) . "] $error - $code $errorNo");
        }

        curl_close($handle);
    }
}
