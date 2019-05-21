<?php


namespace LogEngine\Models\Context;


class TransactionContext implements \JsonSerializable
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

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'request' => $this->request,
            'response' => $this->response,
            'user' => $this->user,
            'custom' => $this->custom,
        ];
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}