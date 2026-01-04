<?php

namespace Hudsxn\Introspection\Structures;

use Hudsxn\Introspection\Traits\Attributes;
use Hudsxn\Introspection\Traits\CommonFields;
use Hudsxn\Introspection\Traits\Modifiers;
use Hudsxn\Introspection\Traits\Typed;
use ReflectionClassConstant;

/**
 * Class Constant
 *
 * Represents a class constant in PHP with full introspection support.
 *
 * Features:
 * - Name of the constant
 * - Modifiers (public, private, protected, etc.)
 * - Typed value (type, default value, optional, by-reference)
 * - Associated attributes
 * - PHP source generation via the SourceGenerator contract
 *
 * to append PHP code lines to an array,
 * which is more memory-efficient and performant than string concatenation.
 *
 * @property string $name  Name of the constant
 */
class Constant
{
    use CommonFields;
    use Modifiers;
    use Typed;
    use Attributes;

    public function from(ReflectionClassConstant $const): static
    {
        // -------------------- Name --------------------
        $this->setName($const->getName());

        // -------------------- Modifiers --------------------
        $mod = $const->getModifiers();
        $this->setPublic(($mod & ReflectionClassConstant::IS_PUBLIC) !== 0)
            ->setProtected(($mod & ReflectionClassConstant::IS_PROTECTED) !== 0)
            ->setPrivate(($mod & ReflectionClassConstant::IS_PRIVATE) !== 0);

        // -------------------- Value / Type --------------------
        $value = $const->getValue();
        $this->setValue($value);
        $this->setType(gettype($value));

        // -------------------- Attributes --------------------
        foreach ($const->getAttributes() as $attribute) {
            $this->addAttribute(
                (new Attribute())->from($attribute)
            );
        }

        return $this;
    }

    public function toSource(array &$lines)
    {
        $lines[] = '->addConstant(';
        $lines[] = 'new ' . static::class . '()';
        $lines[] = '->setName(' . var_export($this->getName(), true) . ')';

        $this->appendModifiers($lines);
        
        $this->addTypedSource($lines);
        foreach($this->attributes as $attribute)
        {
            $lines[] = '->addAttribute(';
            $attribute->toSource($lines);
            $lines[] = ')';
        }

        $lines[] = ')';
    }
}
