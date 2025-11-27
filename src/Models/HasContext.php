<?php

declare(strict_types=1);

namespace Inspector\Models;

use function is_string;

/**
 ** @mixin Model
 */
trait HasContext
{
    public array $context = [];

    /**
     * Add contextual information.
     * If the key exists, it merges the given data instead of overwriting.
     */
    public function addContext(string $key, mixed $data): static
    {
        $this->context[$key] = $data;

        return $this;
    }

    /**
     * Set the entire context bag.
     */
    public function setContext(array $data): static
    {
        $this->context = $data;
        return $this;
    }

    /**
     * Get context items.
     */
    public function getContext(?string $label = null): array
    {
        if (is_string($label)) {
            return $this->context[$label];
        }

        return $this->context;
    }
}
