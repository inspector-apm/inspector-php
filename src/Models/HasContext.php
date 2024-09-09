<?php


namespace Inspector\Models;


trait HasContext
{
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
        $this->context[$key] = $data;

        return $this;
    }

    /**
     * Set the entire context bag.
     *
     * @param array $data
     * @return $this
     */
    public function setContext(array $data)
    {
        $this->context = $data;
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
        if (\is_string($label)) {
            return $this->context[$label];
        }

        return $this->context;
    }
}
