<?php

namespace Hudsxn\Introspection\Structures;

use Hudsxn\Introspection\Traits\Attributes;
use Hudsxn\Introspection\Traits\CommonFields;
use Hudsxn\Introspection\Traits\Modifiers;
use Hudsxn\Introspection\Traits\Typed;
use ReflectionProperty;

/**
 * Class Property
 *
 * Represents a PHP class property with full introspection capabilities.
 *
 * Features:
 * - Name of the property
 * - Modifiers (public, private, protected, static, readonly, etc.)
 * - Typed value (type, default value, optional, by-reference)
 * - Associated attributes
 * - PHP source generation via SourceGenerator
 *
 * to append PHP code lines to an array,
 * which is more memory-efficient and performant than string concatenation.
 *
 * @property string $name Name of the property
 */
class Property
{
    use CommonFields;
    use Modifiers;
    use Typed;
    use Attributes;

    public function from(ReflectionProperty $prop): static
    {
        // -------------------- Name --------------------
        $this->setName($prop->getName());

        // -------------------- Modifiers --------------------
        $this->setPublic($prop->isPublic())
            ->setProtected($prop->isProtected())
            ->setPrivate($prop->isPrivate())
            ->setStatic($prop->isStatic())
            ->setReadOnly($prop->isReadOnly());

        // -------------------- Type --------------------
        $type = $prop->getType();
        if ($type !== null) {
            $this->setType($type->allowsNull() ? '?' . $type->__tostring() : $type->__tostring());
        } else {
            $this->setType('mixed');
        }

        // -------------------- Default Value --------------------
        // Note: ReflectionProperty::getDefaultValue is static, so we fetch defaults from the class
        $defaults = $prop->getDeclaringClass()?->getDefaultProperties();
        if (array_key_exists($prop->getName(), $defaults)) {
            $this->setDefaultValue($defaults[$prop->getName()]);
        }

        // -------------------- Attributes --------------------
        foreach ($prop->getAttributes() as $attribute) {
            $this->addAttribute(
                (new Attribute())->from($attribute)
            );
        }

        return $this;
    }

    public function toSource(array &$lines)
    {
        $lines[] = '->addProperty(';
        $lines[] = 'new ' . static::class . '()';
        $lines[] = '->setName(' . var_export($this->getName(), true) . ')';

        $this->appendModifiers($lines);
        
        foreach($this->attributes as $attribute)
        {
            $lines[] = '->addAttribute(';
            $attribute->toSource($lines);
            $lines[] = ')';
        }
        
        $this->addTypedSource($lines);

        $lines[] = ')';
    }

}
