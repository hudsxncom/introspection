<?php

namespace Hudsxn\Introspection\Structures;

use Hudsxn\Introspection\Introspector;
use Hudsxn\Introspection\Traits\ClassLike;

/**
 * Class TraitObj
 *
 * Represents a PHP trait for introspection purposes.
 *
 * Features:
 * - Inherits ClassLike functionality (namespace, type, properties, methods, constants, modifiers, attributes, traits, extends)
 * - Type is fixed as TYPE_TRAIT
 * - Can generate PHP source code for reconstruction
 *
 * to append PHP code lines to an array,
 * which is more memory-efficient and performant than string concatenation.
 *
 * @property string $namespace Namespace of the trait
 * @property int    $type      Always TYPE_TRAIT
 */
class TraitObj
{
    use ClassLike;

    /**
     * Constructor.
     *
     * Sets the type to TYPE_TRAIT automatically.
     */
    public function __construct()
    {
        $this->setType(Introspector::TYPE_TRAIT);
    }
}
