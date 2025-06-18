<?php

namespace Inspector\Models;

trait HasContext
{
    public array $context = [];

    /**
     * Add contextual information.
     * If the key exists, it merges the given data instead of overwriting.
     */
    public function addContext(string $key, mixed $data): Model
    {
        $this->context[$key] = $data;

        return $this;
    }

    /**
     * Set the entire context bag.
     */
    public function setContext(array $data): Model
    {
        $this->context = $data;
        return $this;
    }

    /**
     * Get context items.
     */
    public function getContext(?string $label = null): array
    {
        if (\is_string($label)) {
            return $this->context[$label];
        }

        return $this->context;
    }
}
