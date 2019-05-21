<?php


namespace LogEngine\Models\Context;


class User implements \JsonSerializable
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
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
        ];
    }

    public function __toString()
    {
        return json_encode($this->jsonSerialize());
    }
}