<?php

namespace Hudsxn\Introspection\Traits;

/**
 * Trait CommonFields
 *
 * Provides a standard `name` property and fluent getter/setter for class-like structures
 * such as properties, methods, arguments, attributes, and other introspection objects.
 *
 * This trait ensures consistency in handling names across all structures in the library.
 *
 * @property string $name The name of the structure (e.g., property, method, argument)
 */
trait CommonFields
{
    use CodeGenerator;

    /**
     * The name of the structure.
     *
     * @var string
     */
    private string $name;

    /**
     * Set the name of this structure.
     *
     * This method is fluent and returns the instance itself.
     *
     * @param string $name The name to assign.
     * @return static Returns self for method chaining.
     */
    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of this structure.
     *
     * @return string The current name.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
