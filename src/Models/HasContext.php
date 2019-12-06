<?php


namespace Inspector\Models;


trait HasContext
{
    /**
     * Add contextual information.
     *
     * @param string $label
     * @param mixed $data
     * @return $this
     */
    public function addContext($label, $data)
    {
        $this->context[$label] = $data;
        return $this;
    }

    /**
     * Set context.
     *
     * @param array $context
     * @return $this
     */
    public function setContext(array $context)
    {
        $this->context = $context;
        return $this;
    }
}
