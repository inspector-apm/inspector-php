<?php


namespace Inspector\Models;


use Exception;
use Inspector\Exceptions\InspectorException;
use Inspector\Models\Context\TransactionContext;

class Transaction extends AbstractModel
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
     * @var string
     */
    protected $result;

    /**
     * @var TransactionContext
     */
    protected $context;

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
        $this->context = new TransactionContext();
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

    public function withUser($id, $username = null, $email = null): Transaction
    {
        $this->getContext()->getUser()
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

        throw new InspectorException('Can\'t create unique transaction hash.');
    }

    /**
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'model' => self::MODEL_NAME,
            'type' => $this->type,
            'name' => $this->name,
            'hash' => $this->hash,
            'timestamp' => $this->timestamp,
            'duration' => $this->duration,
            'result' => $this->result,
            'context' => $this->context->jsonSerialize(),
        ];
    }
}