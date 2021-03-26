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
        $curl = $this->buildCurlCommand($data);

        // Determine if the payload is a file.
        $isFile = function ($payload) {
            return substr($payload, 0, 1) === '@';
        };

        // Curl will run in the background
        if (OS::isWin()) {
            $cmd = "start /B {$curl} > NUL";
            if ($isFile($data)) {
                $cmd .= ' & timeout 1 > NUL & del /f ' . str_replace('@', '', $data);
            }
        } else {
            $cmd = "({$curl} > /dev/null 2>&1";

            if ($isFile($data)) {
                $cmd.= '; rm ' . str_replace('@', '', $data);
            }

            $cmd.= ')&';
        }

        proc_close(proc_open($cmd, [], $pipes));
    }

    /**
     * Carl command is agnostic between Win and Unix.
     *
     * @param $data
     * @return string
     */
    protected function buildCurlCommand($data): string
    {
        $curl = "{$this->curlPath} -X POST";

        foreach ($this->getApiHeaders() as $name => $value) {
            $curl .= " --header \"$name: $value\"";
        }

        $curl .= " --data {$this->getPayload($data)} {$this->config->getUrl()} --max-time 5";

        if ($this->proxy) {
            $curl .= " --proxy \"{$this->proxy}\"";
        }

        return $curl;
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
