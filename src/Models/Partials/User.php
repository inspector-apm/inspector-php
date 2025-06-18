<?php

namespace Inspector\Models\Partials;

use Inspector\Models\Model;

class User extends Model
{
    public function __construct(
        public string|int|null $id = null,
        public ?string $name = null,
        public ?string $email = null,
    ) {
    }
}
