<?php

namespace Hudsxn\Introspection\Structures;

use Hudsxn\Introspection\Introspector;
use Hudsxn\Introspection\Traits\ClassLike;

/**
 * Class InterfaceObj
 *
 * Represents a PHP interface for introspection purposes.
 *
 * Features:
 * - Inherits ClassLike functionality (namespace, type, properties, methods, constants, modifiers, attributes, traits, extends)
 * - Type is fixed as TYPE_INTERFACE
 * - Can generate PHP source code for reconstruction
 *
 * to append PHP code lines to an array,
 * which is more performant and memory-efficient than string concatenation.
 *
 * @property string $namespace Namespace of the interface
 * @property int    $type      Always TYPE_INTERFACE
 */
class InterfaceObj
{
    use ClassLike;

    /**
     * Constructor.
     *
     * Sets the type to TYPE_INTERFACE automatically.
     */
    public function __construct()
    {
        $this->setType(Introspector::TYPE_INTERFACE);
    }

}
