<?php

namespace LogEngine\Transport;


use LogEngine\Exceptions\LogEngineException;

class CurlTransport extends AbstractApiTransport
{
    /**
     * String template for curl error message.
     *
     * @var string
     */
    const ERROR_CURL = 'Curl returned an error. [Error no: %d] [HTTP code: %d] [Message: "%s"] [Response: "%s"]';

    /**
     * String template for curl success message.
     *
     * @var string
     */
    const SUCCESS_CURL = 'Curl sent data successfully. [HTTP code: %d] [Response: "%s"]';

    /**
     * CurlTransport constructor.
     *
     * @param Configuration $configuration
     * @param array $options
     * @throws LogEngineException
     */
    public function __construct($configuration, array $options = array())
    {
        parent::__construct($configuration, $options);

        // System need to have CURL available
        if (!function_exists('curl_init')) {
            throw new LogEngineException('cURL PHP extension is not available');
        }
    }

    /**
     * Deliver items to LOG Engine.
     *
     * @param string $data
     */
    protected function sendChunk($data)
    {
        $headers = array();

        foreach ($this->getApiHeaders() as $name => $value) {
            $headers[] = "$name: $value";
        }

        $handle = curl_init($this->config->getUrl());

        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_TIMEOUT, 5);
        curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        if ($this->proxy) {
            curl_setopt($handle, CURLOPT_PROXY, $this->proxy);
        }
        $response = curl_exec($handle);
        $errorNo = curl_errno($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);

        if (0 !== $errorNo || 200 !== $code) {
            $this->logError(self::ERROR_CURL, $errorNo, $code, $error, $response);
        } else {
            $this->logDebug(self::SUCCESS_CURL, $code, $response);
        }

        curl_close($handle);
    }
}