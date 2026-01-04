<?php

namespace Hudsxn\Introspection\Structures;

use Hudsxn\Introspection\Traits\Attributes;
use Hudsxn\Introspection\Traits\CommonFields;
use Hudsxn\Introspection\Traits\HasArguments;
use Hudsxn\Introspection\Traits\Modifiers;
use ReflectionMethod;

/**
 * Class Method
 *
 * Represents a PHP method with full introspection capabilities.
 *
 * Features:
 * - Name of the method
 * - Arguments (via HasArguments trait)
 * - Modifiers (public, private, protected, static, final, abstract, etc.)
 * - Return type
 * - Associated attributes
 * - PHP source generation via SourceGenerator
 *
 * to append PHP code lines to an array,
 * which is more memory-efficient and performant than string concatenation.
 *
 * @property string $name       Name of the method
 * @property string $returnType Return type of the method
 */
class Method
{
    use CommonFields;
    use HasArguments;
    use Modifiers;
    use Attributes;

    /** @var string The return type of the method */
    private string $returnType;

    // ------------------ Return Type ------------------

    /**
     * Set the return type of the method.
     *
     * @param string $returnType
     * @return static Fluent self reference
     */
    public function setReturnType(string $returnType): static
    {
        $this->returnType = $returnType;
        return $this;
    }

    /**
     * Get the return type of the method.
     *
     * Defaults to 'mixed' if no type is set.
     *
     * @return string
     */
    public function getReturnType(): string
    {
        return $this->returnType ?? 'mixed';
    }

    public function from(ReflectionMethod $method): static
    {
        $this->setName($method->getName());

        // -------------------- Modifiers --------------------
        $this->setPublic($method->isPublic())
            ->setProtected($method->isProtected())
            ->setPrivate($method->isPrivate())
            ->setStatic($method->isStatic())
            ->setFinal($method->isFinal())
            ->setAbstract($method->isAbstract());

        // -------------------- Return Type --------------------
        $returnType = $method->getReturnType();
        if ($returnType !== null) {
            $this->setReturnType(
                $returnType->allowsNull()
                    ? '?' . $returnType->__tostring()
                    : $returnType->__tostring()
            );
        } else {
            $this->setReturnType('mixed');
        }

        // -------------------- Arguments --------------------
        foreach ($method->getParameters() as $param) {
            $this->addArgument(new Argument()->from($param));
        }

        // -------------------- Attributes --------------------
        foreach ($method->getAttributes() as $attribute) {
            $this->addAttribute(
                (new Attribute())->from($attribute)
            );
        }

        return $this;
    }

    public function toSource(array &$lines): void
    {
        $lines[] = '->addMethod(';
        $lines[] = '(new ' . static::class . '()';
        
        // Name
        $lines[] = '    ->setName(' . var_export($this->getName(), true) . ')';
        
        // Return type
        $lines[] = '    ->setReturnType(' . var_export($this->getReturnType(), true) . ')';
        
        // Modifiers
        $modifiers = [
            'setPublic'    => $this->isPublic() ?? false,
            'setProtected' => $this->isProtected() ?? false,
            'setPrivate'   => $this->isPrivate() ?? false,
            'setStatic'    => $this->isStatic() ?? false,
            'setFinal'     => $this->isFinal() ?? false,
            'setAbstract'  => $this->isAbstract() ?? false,
        ];
        foreach ($modifiers as $method => $value) {
            $lines[] = '    ->' . $method . '(' . var_export($value, true) . ')';
        }

        // Arguments (from HasArguments trait)
        foreach ($this->getArguments() as $argument) {
            $argument->toSource($lines); // expects the Argument class to have this
        }

        // Attributes
        foreach ($this->getAttributes() as $attribute) {
            $lines[] = '    ->addAttribute(';
            $attribute->toSource($lines); // expects the Attribute class to have this
            $lines[] = '    )';
        }

        $lines[] = ')'; // close new Method
        $lines[] = ')'; // close addMethod
    }

}
