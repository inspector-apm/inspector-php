<?php


namespace Inspector\Models\Context;


class TransactionContext extends AbstractContext
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

    public function hasContent(): bool
    {
        return !empty($this->custom) ||
            $this->user->hasContent() ||
            $this->request->hasContent() ||
            $this->response->hasContent();
    }

    /**
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'request' => $this->request->toArray(),
            'response' => $this->response->toArray(),
            'user' => $this->user->toArray(),
            'custom' => $this->custom,
        ];
    }
}