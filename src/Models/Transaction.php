<?php


namespace LogEngine\Models;


use Exception;
use LogEngine\Exceptions\LogEngineApmException;
use LogEngine\Models\Context\TransactionContext;

class Transaction implements \JsonSerializable
{
    const MODEL_NAME = 'transaction';
    const TYPE_REQUEST = 'request';
    const TYPE_PROCESS = 'process';

    /**
     * Keyword of specific relevance in the service's domain (eg:  'request', 'backgroundjob').
     *
     * @var string
     */
    protected $type;

    /**
     * Name reference for grouping transactions.
     *
     * @var string
     */
    protected $name;

    /**
     * Unique identifier.
     *
     * @var string
     */
    protected $hash;

    /**
     * Start time of transaction.
     *
     * @var float
     */
    protected $start;

    /**
     * Number of milliseconds until Transaction ends.
     *
     * @var float
     */
    protected $duration = 0.0;

    /**
     * @var TransactionContext
     */
    protected $context;

    /**
     * PHP backtrace.
     *
     * @var array
     */
    protected $backtrace = [];

    /**
     * Limit the number of stack frames returned.
     *
     * @var int
     */
    protected $backtraceLimit = 0;

    /**
     * @var string
     */
    protected $result;

    /**
     * Transaction constructor.
     *
     * @param string $name
     * @throws Exception
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->type = !empty($_SERVER['REQUEST_METHOD']) ? self::TYPE_REQUEST : self::TYPE_PROCESS;
        $this->hash = $this->generateUniqueHash();
        $this->start = microtime(true);
        $this->context = new TransactionContext();
    }

    public function start(): Transaction
    {
        $this->start = microtime(true);
        return $this;
    }

    public function end(): Transaction
    {
        $this->duration = round((microtime(true) - $this->start) * 1000, 2); // milliseconds
        $this->backtrace = debug_backtrace($this->backtraceLimit);
        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): Transaction
    {
        $this->type = $type;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Transaction
    {
        $this->name = $name;
        return $this;
    }

    public function getContext(): TransactionContext
    {
        return $this->context;
    }

    public function getBacktraceLimit(): int
    {
        return $this->backtraceLimit;
    }

    public function setBacktraceLimit(int $backtraceLimit): Transaction
    {
        $this->backtraceLimit = $backtraceLimit;
        return $this;
    }

    public function withUser($id, $username = null, $email = null): Transaction
    {
        $this->context->getUser()
            ->setId($id)
            ->setUsername($username)
            ->setEmail($email);

        return $this;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    /**
     * HTTP status code for HTTP-related transactions.
     *
     * @param string $result
     * @return Transaction
     */
    public function setResult(string $result): Transaction
    {
        $this->result = $result;
        return $this;
    }

    public function addCustomContext($key, $value): Transaction
    {
        $this->context->addCustom($key, $value);
        return $this;
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

        throw new LogEngineApmException('Can\'t create unique transaction hash.');
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'model' => self::MODEL_NAME,
            'type' => $this->type,
            'name' => $this->name,
            'hash' => $this->hash,
            'start' => $this->start,
            'duration' => $this->duration,
            'result' => $this->result,
            'context' => $this->context->jsonSerialize(),
            'backtrace' => $this->backtrace,
        ];
    }

    /**
     * String representation.
     *
     * @return false|string
     */
    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}