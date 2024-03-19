<?php


namespace Inspector\Models;


trait HasContext
{
    /**
     * The context bag.
     *
     * @var array
     */
    protected $context = [];

    /**
     * Add contextual information.
     * If the key exists it merge the given data instead of overwrite.
     *
     * @param string $key
     * @param mixed $data
     * @return $this
     */
    public function addContext($key, $data)
    {
        $this->context[$key] = array_key_exists($key, $this->context)
            ? array_merge($this->context[$key], $data)
            : $data;

        return $this;
    }

    /**
     * Set the entire context bag.
     *
     * @param array $context
     * @return $this
     */
    public function setContext(array $context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Get context items.
     *
     * @param string|null $label
     * @return mixed
     */
    public function getContext(?string $label = null)
    {
        if (is_string($label)) {
            return $this->context[$label];
        }

        return $this->context;
    }
}
