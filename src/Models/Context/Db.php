<?php


namespace Inspector\Models\Context;


class Db extends AbstractContext
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
     * @var array
     */
    protected $bindings = [];

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

    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function setBindings(array $bindings): Db
    {
        $this->bindings = $bindings;
        return $this;
    }

    public function hasContent(): bool
    {
        return $this->type != null ||
            $this->sql != null;
    }

    /**
     * Array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'sql' => $this->sql,
            'bindings' => $this->bindings,
        ];
    }
}