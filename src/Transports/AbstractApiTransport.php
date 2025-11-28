<?php

declare(strict_types=1);

namespace Inspector\Transports;

use Inspector\Exceptions\InspectorException;
use Inspector\Models\Model;

use function array_chunk;
use function base64_encode;
use function ceil;
use function count;
use function file_put_contents;
use function floor;
use function json_encode;
use function preg_match;
use function strlen;
use function sys_get_temp_dir;
use function tempnam;

use const LOCK_EX;

abstract class AbstractApiTransport implements TransportInterface
{
    /**
     * Queue of messages to send.
     *
     * @var array
     */
    protected array $queue = [];

    /**
     * AbstractApiTransport constructor.
     *
     * @throws InspectorException
     */
    public function __construct(/**
     * Key to authenticate remote calls.
     */
        protected \Inspector\Configuration $config
    ) {
        $this->verifyOptions($this->config->getOptions());
    }

    /**
     * Verify if given options match constraints.
     *
     * @throws InspectorException
     */
    protected function verifyOptions(array $options): void
    {
        foreach ($this->getAllowedOptions() as $name => $regex) {
            if (isset($options[$name])) {
                $value = $options[$name];
                if (!preg_match($regex, $value)) {
                    throw new InspectorException("Option '$name' has invalid value");
                }
            }
        }
    }

    /**
     * Get the current queue.
     */
    public function getQueue(): array
    {
        return $this->queue;
    }

    /**
     * Empty the queue.
     */
    public function resetQueue(): TransportInterface
    {
        $this->queue = [];
        return $this;
    }

    /**
     * Add a message to the queue.
     */
    public function addEntry(Model $model): TransportInterface
    {
        // Force insert when dealing with errors.
        if ($model->model === 'error' || count($this->queue) <= $this->config->getMaxItems()) {
            $this->queue[] = $model;
        }
        return $this;
    }

    /**
     * Deliver everything on the queue to LOG Engine.
     */
    public function flush(): TransportInterface
    {
        if ($this->queue === []) {
            return $this;
        }

        $this->send($this->queue);

        $this->resetQueue();
        return $this;
    }

    /**
     * Send data chunks based on MAX_POST_LENGTH.
     */
    public function send(array $items): void
    {
        $json = json_encode($items);
        $jsonLength = strlen($json);
        $count = count($items);

        if ($jsonLength > $this->config->getMaxPostSize()) {
            if ($count === 1) {
                // It makes no sense to divide into chunks, just try to send data via file
                $this->sendViaFile(base64_encode($json));
                return;
            }

            $chunkSize = (int) floor($count / ceil($jsonLength / $this->config->getMaxPostSize()));
            $chunks = array_chunk($items, $chunkSize > 0 ? $chunkSize : 1);

            foreach ($chunks as $chunk) {
                $this->send($chunk);
            }
        } else {
            $this->sendChunk(base64_encode($json));
        }
    }

    /**
     * Put data into a file and provide CURL with the file path.
     */
    protected function sendViaFile(string $data): void
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'inspector');

        file_put_contents($tmpfile, $data, LOCK_EX);

        $this->sendChunk('@'.$tmpfile);
    }

    /**
     * Send a portion of the load to the remote service.
     */
    abstract protected function sendChunk(string $data): void;

    /**
     * List of available transport options with validation regex.
     *
     * ['param-name' => 'regex']
     */
    protected function getAllowedOptions(): array
    {
        return [
            'proxy' => '/.+/', // Custom url for
            'debug' => '/^(0|1)?$/',  // boolean
        ];
    }

    protected function getApiHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Inspector-Key' => $this->config->getIngestionKey(),
            'X-Inspector-Version' => $this->config->getVersion(),
        ];
    }
}
