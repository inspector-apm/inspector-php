<?php

namespace LogEngine\Transport;


use LogEngine\Exceptions\LogEngineException;

/**
 * This transport collects log data until the end of processing.
 * It sends data executing shell curl and sending it to background (Asynchronous mode).
 */
class AsyncTransport extends AbstractApiTransport
{
    /**
     * CURL command path.
     *
     * @var string
     */
    protected $curlPath = 'curl';

    /**
     * Max size of a POST request content.
     *
     * @var integer
     */
    const MAX_POST_LENGTH = 65536;  // 1024 * 64

    /**
     * @var string
     */
    const ERROR_LENGTH = 'Batch is too long: %s';

    /**
     * String template for curl error message.
     *
     * @var string
     */
    const ERROR_CURL = 'Command returned an error. [Command: "%s"] [Return code: %d] [Message: "%s"]';

    /**
     * String template for curl success message.
     *
     * @var string
     */
    const SUCCESS_CURL = 'Command sent. [Command: "%s"]';

    /**
     * AsyncTransport constructor.
     *
     * @param Configuration $configuration
     * @param array $options
     * @throws LogEngineException
     */
    public function __construct($configuration, array $options = array())
    {
        parent::__construct($configuration, $options);

        if (!function_exists('exec')) {
            throw new LogEngineException("PHP function 'exec' is not available, is it disabled for security reasons?");
        }

        if ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {
            throw new LogEngineException('Exec transport is not supposed to work on Windows OS');
        }
    }

    /**
     * List of available transport's options with validation regex.
     *
     * ['param-name' => 'regex']
     *
     * @return mixed
     */
    protected function getAllowedOptions()
    {
        return array_merge(parent::getAllowedOptions(), array(
            'curlPath' => '/.+/',
        ));
    }

    /**
     * Send data chunks based on MAX_POST_LENGTH.
     *
     * @param array $items
     */
    protected function send($items)
    {
        $json = $this->buildRequestData($items);
        $jsonLength = strlen($json);
        $count = count($items);

        if ($jsonLength > self::MAX_POST_LENGTH) {
            if (1 === $count) {
                // it makes no sense to divide into chunks, just fail
                $this->logError(self::ERROR_LENGTH, $jsonLength);
                return;
            }
            $maxCount = floor($count / ceil($jsonLength / self::MAX_POST_LENGTH));
            $chunks = array_chunk($items, $maxCount);
            foreach ($chunks as $chunk) {
                $this->send($chunk);
            }
        } else {
            $this->sendChunk($json);
        }
    }

    /**
     * Send a log entry to the remote service.
     *
     * @param string $data
     * @return mixed
     */
    protected function sendChunk(string $data)
    {
        $cmd = "$this->curlPath -X POST";

        foreach ($this->getApiHeaders() as $name => $value) {
            $cmd .= " --header \"$name: $value\"";
        }

        $escapedData = $this->escapeArg($data);

        $cmd .= " --data '$escapedData' '".$this->config->getUrl()."' --max-time 5";
        if ($this->proxy) {
            $cmd .= " --proxy '$this->proxy'";
        }

        if ($this->debug) {
            $cmd .= ' --verbose';
        } else {
            // return immediately while curl will run in the background
            $cmd .= ' > /dev/null 2>&1 &';
        }

        $output = array();
        $r = exec($cmd, $output, $result);

        // if debug mode is off, it makes no sense to check result,
        // because command is send to background
        if ($this->debug) {
            if ($result !== 0) {
                // curl returned some error
                $this->logError(self::ERROR_CURL, $cmd, $result, implode(' ', $output));
            } else {
                $this->logDebug(self::SUCCESS_CURL, $cmd);
            }
        }
    }

    /**
     * Escape character to use in the CLI.
     *
     * @param $string
     * @return mixed
     */
    protected function escapeArg($string)
    {
        // http://stackoverflow.com/a/1250279/871861
        return str_replace("'", "'\"'\"'", $string);
    }
}