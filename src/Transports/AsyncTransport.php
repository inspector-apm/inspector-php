<?php


namespace Inspector\Transports;


use Inspector\Configuration;
use Inspector\Exceptions\InspectorException;
use Inspector\OS;

class AsyncTransport extends AbstractApiTransport
{
    /**
     * CURL command path.
     *
     * @var string
     */
    protected $curlPath = 'curl';

    /**
     * AsyncTransport constructor.
     *
     * @param Configuration $configuration
     * @throws InspectorException
     */
    public function __construct($configuration)
    {
        if (!function_exists('proc_open')) {
            throw new InspectorException("PHP function 'proc_open' is not available, is it disabled for security reasons?");
        }

        parent::__construct($configuration);
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
        return array_merge(parent::getAllowedOptions(), [
            'curlPath' => '/.+/',
        ]);
    }

    /**
     * Send a portion of the load to the remote service.
     *
     * @param string $data
     * @return void|mixed
     */
    public function sendChunk($data)
    {
        $cmd = "{$this->curlPath} -X POST";

        foreach ($this->getApiHeaders() as $name => $value) {
            $cmd .= " --header \"$name: $value\"";
        }

        $cmd .= " --data {$this->getPayload($data)} {$this->config->getUrl()} --max-time 5";

        if ($this->proxy) {
            $cmd .= " --proxy \"{$this->proxy}\"";
        }

        // Curl will run in the background
        if (OS::isWin()) {
            $cmd = "start /B {$cmd} > NUL";
            if (substr($data, 0, 1) === '@') {
                $cmd .= ' & timeout 2 & del /f ' . str_replace('@', '', $data);
            }
        } else {
            $cmd .= " > /dev/null 2>&1 &";
        }

        proc_close(proc_open($cmd, [], $pipes));
    }

    /**
     * Escape character to use in the CLI.
     *
     * Compatible to send data via file path: @../file/path.dat
     *
     * @param $string
     * @return mixed
     */
    protected function getPayload($string)
    {
        return OS::isWin()
            // https://stackoverflow.com/a/30224062/5161588
            ? '"' . str_replace('"', '""', $string) . '"'
            // http://stackoverflow.com/a/1250279/871861
            : "'" . str_replace("'", "'\"'\"'", $string) . "'";
    }
}
