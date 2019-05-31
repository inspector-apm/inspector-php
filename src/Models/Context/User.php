<?php


namespace Inspector\Models\Context;


class User extends AbstractContext
{
    /**
     * Identifier of the logged in user, e.g. the primary key of the user
     */
    protected $id;

    /**
     * The username of the logged in user
     */
    protected $username;

    /**
     * Email of the logged in user
     */
    protected $email;

    public function setId($id): User
    {
        $this->id = $id;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail($email): User
    {
        $this->email = $email;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername($username): User {
        $this->username = $username;
        return $this;
    }

    public function hasContent(): bool
    {
        return $this->id != null ||
            $this->email != null ||
            $this->username != null;
    }

    /**
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
        ];
    }
}