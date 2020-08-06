<?php

namespace Inspector\Transports;


use Inspector\Configuration;
use Inspector\Exceptions\InspectorException;

class CurlTransport extends AbstractApiTransport
{
    /**
     * CurlTransport constructor.
     *
     * @param Configuration $configuration
     * @throws InspectorException
     */
    public function __construct($configuration)
    {
        // System need to have CURL available
        if (!function_exists('curl_init') || !function_exists('curl_multi_init')) {
            throw new InspectorException('cURL PHP extension is not available');
        }

        parent::__construct($configuration);
    }

    /**
     * Deliver items to Inspector.
     *
     * @param string $data
     */
    public function sendChunk($data)
    {
        $headers = [];

        foreach ($this->getApiHeaders() as $name => $value) {
            $headers[] = "$name: $value";
        }

        $handle = curl_init($this->config->getUrl());

        curl_setopt($handle, CURLOPT_POST, 1);

        // Tell cURL that it should only spend 10 seconds trying to connect to the URL in question.
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        // A given cURL operation should only take 30 seconds max.
        curl_setopt($handle, CURLOPT_TIMEOUT, 10);

        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
        if ($this->proxy) {
            curl_setopt($handle, CURLOPT_PROXY, $this->proxy);
        }

        $this->executeAsync($handle);
    }

    /**
     * @param resource|false $handle a cURL handle on success, false on errors.
     */
    protected function executeAsync($handle)
    {
        if ($handle === false) {
            error_log(date('Y-m-d H:i:s') . " - [Warning] [" . get_class($this) . "] CURL initialization failed.");
        }

        //create the multiple cURL handle
        $mh = curl_multi_init();

        //add the handle
        curl_multi_add_handle($mh, $handle);

        // Execute the request
        $status = curl_multi_exec($mh, $active);

        if (!$active) {
            error_log(date('Y-m-d H:i:s') . " - [Warning] [" . get_class($this) . "] CURL status: {$status}");
        }

        //close the handles
        curl_multi_remove_handle($mh, $handle);
        curl_multi_close($mh);
    }
}
