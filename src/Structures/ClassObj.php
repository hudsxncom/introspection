<?php

namespace Hudsxn\Introspection\Structures;

use Hudsxn\Introspection\Introspector;
use Hudsxn\Introspection\Traits\ClassLike;

/**
 * Class ClassObj
 *
 * Represents a PHP class for introspection purposes.
 *
 * Features:
 * - Inherits all ClassLike functionality (namespace, type, properties, methods, constants, modifiers, attributes, traits, extends)
 * - Tracks implemented interfaces
 * - Generates PHP source code for reconstruction
 *
 * to append PHP source lines to an array,
 * which is more performant and memory-efficient than string concatenation.
 *
 * @property string   $namespace   The namespace of the class
 * @property int      $type        Always TYPE_CLASS for this structure
 * @property string[] $implements  List of fully qualified interface names implemented by this class
 */
class ClassObj
{
    use ClassLike;

    /**
     * @var string[] List of fully qualified interface names implemented by this class
     */
    private array $implements = [];

    /**
     * Constructor.
     *
     * Sets the type to TYPE_CLASS automatically.
     */
    public function __construct()
    {
        $this->setType(Introspector::TYPE_CLASS);
    }

    // ------------------ Implements ------------------

    /**
     * Add an interface that this class implements.
     *
     * @param string $implements Fully qualified interface name
     * @return static Fluent self reference
     */
    public function addImplement(string $implements): static
    {
        $this->implements[] = $implements;
        return $this;
    }

    /**
     * Check if this class implements a specific interface.
     *
     * @param string $target Fully qualified interface name
     * @return bool
     */
    public function doesImplement(string $target): bool
    {
        return in_array($target, $this->implements, true);
    }

    /**
     * Get all interfaces implemented by this class.
     *
     * @return string[]
     */
    public function getImplements(): array
    {
        return $this->implements;
    }
}
