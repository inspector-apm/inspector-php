<?php


namespace LogEngine\Models\Context;


class ErrorContext extends AbstractContext
{
    /**
     * @var User
     */
    protected $user;

    /**
     * ErrorContext constructor.
     */
    public function __construct()
    {
        $this->user = new User();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function hasContent(): bool
    {
        return $this->user->hasContent();
    }

    public function toArray(): array
    {
        return [
            'user' => $this->user->toArray()
        ];
    }
}