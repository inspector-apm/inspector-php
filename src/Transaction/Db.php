<?php


namespace LogEngine\Transaction;


use LogEngine\Transaction\Context\Models\User;

class Db
{
    /**
     * It will be "sql" for any sql database. For others could be the name of the database engine: "redis", "mongo", etc.
     *
     * @var string
     */
    protected $type;

    /**
     * The SQL code.
     *
     * @var string
     */
    protected $sql;

    /**
     * @var User
     */
    protected $user;

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Db
    {
        $this->type = $type;
        return $this;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function setSql(string $sql): Db
    {
        $this->sql = $sql;
        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): Db
    {
        $this->user = $user;
        return $this;
    }

    public function hasContent(): bool
    {
        return $this->type != null ||
            $this->sql != null ||
            $this->user->hasContent();
    }
}