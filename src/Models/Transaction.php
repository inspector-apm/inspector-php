<?php

namespace Inspector\Models;

use Exception;
use Inspector\Exceptions\InspectorException;
use Inspector\Models\Partials\Host;
use Inspector\Models\Partials\Http;
use Inspector\Models\Partials\User;

class Transaction extends PerformanceModel
{
    public ?string $model = 'transaction';
    public string $type = 'transaction';
    public string $name;
    public string $hash;
    public ?string $result = null;
    public ?Http $http = null;
    public ?User $user = null;
    public ?Host $host = null;
    public ?float $memory_peak = null;

    /**
     * Transaction constructor.
     *
     * @param string $name
     * @throws Exception
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->hash = $this->generateUniqueHash();
        $this->host = new Host();
    }

    /**
     * Mark the current transaction as an HTTP request.
     *
     * @return $this
     */
    public function markAsRequest(): Transaction
    {
        $this->setType('request');
        $this->http = new Http();
        return $this;
    }

    /**
     * Set the type to categorize the transaction.
     *
     * @param string $type
     * @return $this
     */
    public function setType(string $type): Transaction
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Attach user information.
     *
     * @param integer|string $id
     * @param null|string $name
     * @param null|string $email
     * @return $this
     */
    public function withUser($id, ?string $name = null, ?string $email = null): Transaction
    {
        $this->user = new User($id, $name, $email);
        return $this;
    }

    /**
     * Set a string representation of a transaction result (e.g. 'error', 'success', 'ok', '200', etc...).
     *
     * @param string $result
     * @return Transaction
     */
    public function setResult(string $result): Transaction
    {
        $this->result = $result;
        return $this;
    }

    public function end(int|float|null $duration = null): Transaction
    {
        // Sample memory peak at the end of execution.
        $this->memory_peak = $this->getMemoryPeak();
        parent::end($duration);
        return $this;
    }

    public function isEnded(): bool
    {
        return isset($this->duration) && $this->duration > 0;
    }

    public function getMemoryPeak(): float
    {
        return \round((\memory_get_peak_usage() / 1024 / 1024), 2); // MB
    }

    /**
     * Generate a unique transaction hash.
     *
     * http://www.php.net/manual/en/function.uniqid.php
     *
     * @throws \Exception
     */
    public function generateUniqueHash(int $length = 32): string
    {
        if ($length <= 8) {
            $length = 32;
        }

        if (\function_exists('random_bytes')) {
            return \bin2hex(\random_bytes($length));
        } elseif (\function_exists('openssl_random_pseudo_bytes')) {
            return \bin2hex(\openssl_random_pseudo_bytes($length));
        }

        throw new InspectorException('Can\'t create unique transaction hash.');
    }
}
