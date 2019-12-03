<?php


namespace Inspector\Models;


use Exception;
use Inspector\Exceptions\InspectorException;
use Inspector\Models\Partials\Host;
use Inspector\Models\Partials\Http;
use Inspector\Models\Partials\User;

class Transaction extends AbstractModel
{
    const MODEL_NAME = 'transaction';
    const TYPE_REQUEST = 'request';
    const TYPE_PROCESS = 'process';

    /**
     * Transaction constructor.
     *
     * @param string $name
     * @throws Exception
     */
    public function __construct($name)
    {
        $this->model = self::MODEL_NAME;
        $this->name = $name;
        $this->type = !empty($_SERVER['REQUEST_METHOD']) ? self::TYPE_REQUEST : self::TYPE_PROCESS;
        $this->hash = $this->generateUniqueHash();
        $this->host = new Host();

        if ($this->type === self::TYPE_REQUEST) {
            $this->http = new Http;
        }
    }

    /**
     * Attcach user information.
     *
     * @param integer|string $id
     * @param null|string $name
     * @param null|string $email
     * @return $this
     */
    public function withUser($id, $name = null, $email = null)
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

    public function end($duration = null): AbstractModel
    {
        $this->memoryPeak = $this->getMemoryPeak();
        return parent::end($duration);
    }

    public function getMemoryPeak(): float
    {
        if(isset($this->memoryPeak)){
            return $this->memoryPeak;
        }

        return round((memory_get_peak_usage()/1024/1024), 2); // MB
    }

    /**
     * Generate unique ID for grouping events.
     *
     * http://www.php.net/manual/en/function.uniqid.php
     *
     * @param int $length
     * @return string
     * @throws \Exception
     */
    public function generateUniqueHash($length = 32)
    {
        if (!isset($length) || intval($length) <= 8) {
            $length = 32;
        }

        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }

        throw new InspectorException('Can\'t create unique transaction hash.');
    }
}
