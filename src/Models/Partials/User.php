<?php


namespace Inspector\Models\Partials;


use Inspector\Models\Arrayable;

/**
 * Class User
 * @package Inspector\Models\Partials
 *
 * {
 *  id: string,
 *  name: string,
 *  email: string
 * }
 */
class User extends Arrayable
{
    /**
     * User constructor.
     *
     * @param null|string $id
     * @param null|string $name
     * @param null|string $email
     */
    public function __construct($id = null, $name = null, $email = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
    }
}
