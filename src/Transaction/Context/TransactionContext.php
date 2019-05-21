<?php


namespace LogEngine\Transaction\Context;


use LogEngine\Transaction\Context\Models\Request;
use LogEngine\Transaction\Context\Models\Response;
use LogEngine\Transaction\Context\Models\User;

class TransactionContext
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var array
     */
    protected $custom = [];

    /**
     * TransactionContext constructor.
     */
    public function __construct()
    {
        $this->request = new Request();
        $this->response = new Response();
        $this->user = new User();
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCustom($key = null)
    {
        if(is_null($key)){
            return $this->custom;
        }

        if(array_key_exists($key, $this->custom)){
            return $this->custom[$key];
        }

        return null;
    }

    public function addCustom(string $key, $value): TransactionContext
    {
        $this->custom[$key] = $value;
        return $this;
    }

    public function setCustom($collection): TransactionContext
    {
        $this->custom = $collection;
        return $this;
    }

    public function hasCustom()
    {
        return !empty($this->custom);
    }
}