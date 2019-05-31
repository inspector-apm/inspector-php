<?php


namespace Inspector\Models\Context;


class ErrorContext extends AbstractContext
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Request
     */
    protected $request;

    /**
     * ErrorContext constructor.
     */
    public function __construct()
    {
        $this->user = new User();
        $this->request = new Request();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function hasContent(): bool
    {
        return $this->user->hasContent();
    }

    public function toArray(): array
    {
        return [
            'user' => $this->user->toArray(),
            'request' => $this->request->toArray(),
        ];
    }
}