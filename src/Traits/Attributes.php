<?php

namespace Hudsxn\Introspection\Traits;

use Hudsxn\Introspection\Structures\Attribute;

/**
 * Trait Attributes
 *
 * Provides functionality to manage PHP 8 attributes for any class-like structure.
 * 
 * This trait allows adding, retrieving, checking, and appending attributes. 
 * It is designed for introspection purposes and supports generating PHP source code.
 *
 * @property Attribute[] $attributes List of attached Attribute objects
 */
trait Attributes
{
    /**
     * @var Attribute[] List of attached attributes
     */
    private array $attributes = [];

    /**
     * Add an Attribute instance to the collection.
     *
     * @param Attribute $attribute The attribute to add.
     * @return static Returns self for fluent chaining.
     */
    public function addAttribute(Attribute $attribute): static
    {
        $this->attributes[] = $attribute;
        return $this;
    }

    /**
     * Check if an attribute exists by name.
     *
     * @param string $name The fully qualified class name of the attribute.
     * @return bool True if the attribute exists, false otherwise.
     */
    public function hasAttribute(string $name): bool
    {
        foreach ($this->attributes as $attr) {
            if ($attr->getName() === $name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve all attributes.
     *
     * @return Attribute[] Returns a numeric array of all attached attributes.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single attribute by name.
     *
     * Returns the first attribute matching the given name, or null if none exists.
     *
     * @param string $name The fully qualified class name of the attribute.
     * @return Attribute|null The matched Attribute instance or null.
     */
    public function getSingleAttribute(string $name): Attribute|null
    {
        foreach ($this->attributes as $attr) {
            if ($attr->getName() === $name) {
                return $attr;
            }
        }
        return null;
    }
}
