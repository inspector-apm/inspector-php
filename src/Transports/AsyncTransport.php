<?php

namespace Inspector\Transports;

use Inspector\Configuration;
use Inspector\Exceptions\InspectorException;
use Inspector\OS;

class AsyncTransport extends AbstractApiTransport
{
    /**
     * AsyncTransport constructor.
     *
     * @param Configuration $configuration
     * @throws InspectorException
     */
    public function __construct(Configuration $configuration)
    {
        if (!\function_exists('proc_open')) {
            throw new InspectorException("PHP function 'proc_open' is not available.");
        }

        parent::__construct($configuration);
    }

    /**
     * List of available transport options with validation regex.
     *
     * ['param-name' => 'regex']
     *
     * Override to introduce "curlPath".
     */
    protected function getAllowedOptions(): array
    {
        return \array_merge(parent::getAllowedOptions(), [
            'curlPath' => '/.+/',
        ]);
    }

    /**
     * Send a portion of the load to the remote service.
     */
    public function sendChunk(string $data): void
    {
        $curl = $this->buildCurlCommand($data);

        if (OS::isWin()) {
            $cmd = "start /B {$curl} > NUL";
        } else {
            $cmd = "({$curl} > /dev/null 2>&1";

            // Delete temporary file after data transfer
            if (\str_starts_with($data, '@')) {
                $cmd .= '; rm ' . \str_replace('@', '', $data);
            }

            $cmd .= ')&';
        }

        \proc_close(\proc_open($cmd, [], $pipes));
    }

    /**
     * Carl command is agnostic between Win and Unix.
     */
    protected function buildCurlCommand(string $data): string
    {
        $curl = $this->config->getOptions()['curlPath'] ?? 'curl';

        $curl .= " -X POST --ipv4 --max-time 5";

        foreach ($this->getApiHeaders() as $name => $value) {
            $curl .= " --header \"$name: $value\"";
        }

        $curl .= " --data {$data} {$this->config->getUrl()}";

        if (\array_key_exists('proxy', $this->config->getOptions())) {
            $curl .= " --proxy \"{$this->config->getOptions()['proxy']}\"";
        }

        return $curl;
    }
}
