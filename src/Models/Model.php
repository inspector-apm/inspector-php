<?php

declare(strict_types=1);

namespace Inspector\Models;

use ReflectionClass;
use ReflectionProperty;
use JsonSerializable;

use function array_filter;
use function array_flip;
use function array_intersect_key;
use function is_array;
use function is_object;
use function strlen;

abstract class Model implements JsonSerializable
{
    use HasContext;

    public string $model;

    /**
     * Return a subarray that contains only the given keys.
     *
     * @param string[] $keys
     */
    public function only(array $keys): array
    {
        $properties = $this->jsonSerialize();
        return array_intersect_key($properties, array_flip($keys));
    }

    public function jsonSerialize(): array
    {
        return array_filter($this->getProperties(), fn (mixed $value): bool|int =>
            // remove NULL, FALSE, empty strings and empty arrays, but keep values of 0 (zero)
            is_array($value) || is_object($value) ? !empty($value) : strlen((string) ($value ?? '')));
    }

    protected function getProperties(): array
    {
        $properties = [];

        $reflect = new ReflectionClass($this);
        do {
            foreach ($reflect->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                $properties[$property->getName()] = $property->getValue($this);
            }
        } while ($reflect = $reflect->getParentClass());

        return $properties;
    }
}
