<?php

namespace Hudsxn\Introspection\Structures;

use Hudsxn\Introspection\Traits\Attributes;
use Hudsxn\Introspection\Traits\CommonFields;
use Hudsxn\Introspection\Traits\Typed;
use ReflectionParameter;

/**
 * Represents a method or function argument with full introspection capabilities.
 *
 * This class provides comprehensive introspection support for function and method parameters,
 * encapsulating all aspects of argument definition including name, position, type information,
 * default values, variadic behavior, and associated attributes.
 *
 * The class implements the SourceGenerator contract to enable efficient PHP source code
 * generation through array-based line accumulation, avoiding the performance overhead
 * of repeated string concatenation operations.
 *
 * Key capabilities:
 * - Argument identification (name, position in parameter list)
 * - Type system integration (type hints, nullability, mixed types)
 * - Default value handling and optional parameter detection
 * - Variadic argument support (splat operator)
 * - Pass-by-reference detection
 * - PHP 8+ attribute introspection
 * - Bidirectional conversion between ReflectionParameter and structured data
 * - Source code generation for serialization/caching purposes
 *
 * @package Hudsxn\Introspection\Structures
 *
 * @property-read string $name        The argument name (without $ prefix)
 * @property-read int    $position    Zero-based position in the argument list
 * @property-read bool   $isVariadic  Whether the argument uses variadic syntax (...$args)
 * @property-read bool   $isByReference Whether the argument is passed by reference (&$arg)
 * @property-read string $type        The type hint (e.g., 'string', '?int', 'mixed')
 * @property-read bool   $isOptional  Whether the argument has a default value or is variadic
 * @property-read mixed  $defaultValue The default value if available
 *
 * @see ReflectionParameter For the underlying reflection source
 *
 * @author Hudsxn
 */
class Argument
{
    use CommonFields;
    use Typed;
    use Attributes;

    /**
     * Zero-based position of the argument in the parameter list.
     *
     * For example, in `function foo($a, $b, $c)`, $a has position 0,
     * $b has position 1, and $c has position 2.
     *
     * @var int
     */
    private int $position;

    /**
     * Indicates whether the argument is variadic (uses the splat operator).
     *
     * A variadic argument can accept zero or more values and is defined
     * using the `...` operator, such as `...$args`.
     *
     * @var bool
     */
    private bool $isVariadic = false;

    // ==================== Position Management ====================

    /**
     * Retrieves the zero-based position of the argument in the parameter list.
     *
     * @return int The argument position (0 for first argument, 1 for second, etc.)
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Sets the zero-based position of the argument in the parameter list.
     *
     * @param int $position The position index (must be >= 0)
     *
     * @return static Returns the current instance for method chaining
     */
    public function setPosition(int $position): static
    {
        $this->position = $position;
        return $this;
    }

    // ==================== Variadic Management ====================

    /**
     * Determines whether the argument is variadic (uses splat operator).
     *
     * A variadic argument accepts zero or more values of the specified type
     * and is defined using the `...` syntax (e.g., `...$args`).
     *
     * @return bool True if the argument is variadic, false otherwise
     */
    public function isVariadic(): bool
    {
        return $this->isVariadic;
    }

    /**
     * Sets whether the argument is variadic.
     *
     * @param bool $variadic True to mark as variadic, false otherwise
     *
     * @return static Returns the current instance for method chaining
     */
    public function setVariadic(bool $variadic): static
    {
        $this->isVariadic = $variadic;
        return $this;
    }

    // ==================== Reflection Integration ====================

    /**
     * Populates this Argument instance from a ReflectionParameter object.
     *
     * This method performs a comprehensive extraction of all parameter metadata
     * from PHP's reflection system, including:
     * - Basic identification (name, position)
     * - Behavior flags (variadic, pass-by-reference)
     * - Type information with nullability detection
     * - Optional status and default values
     * - All associated PHP 8+ attributes
     *
     * Type handling logic:
     * - If a type hint exists, it's stored with nullable prefix (?) if applicable
     * - If no type hint exists, defaults to 'mixed' for PHP 8+ compatibility
     * - Union and intersection types are preserved as strings
     *
     * @param ReflectionParameter $param The reflection parameter to extract from
     *
     * @return static Returns the current instance for method chaining
     *
     * @throws \ReflectionException If reflection operations fail (rare edge cases)
     */
    public function from(ReflectionParameter $param): static
    {
        // -------------------- Name & Position --------------------
        $this->setName($param->getName())
            ->setPosition($param->getPosition())
            ->setVariadic($param->isVariadic())
            ->setByReference($param->isPassedByReference());

        // -------------------- Type --------------------
        $type = $param->getType();
        if ($type !== null) {
            $this->setType($type->allowsNull() ? '?' . $type->__tostring() : $type->__tostring());
        } else {
            $this->setType('mixed');
        }

        // -------------------- Optional / Default --------------------
        $this->setOptional($param->isOptional());
        if ($param->isDefaultValueAvailable()) {
            $this->setDefaultValue($param->getDefaultValue());
        }

        // -------------------- Attributes --------------------
        foreach ($param->getAttributes() as $attribute) {
            $this->addAttribute(
                (new Attribute())->from($attribute)
            );
        }

        return $this;
    }

    // ==================== Source Generation ====================

    /**
     * Generates PHP source code lines representing this argument definition.
     *
     * This method implements the SourceGenerator contract by appending code lines
     * to the provided array reference. The generated code creates a fluent chain
     * of method calls that reconstructs this Argument instance.
     *
     * Generated code structure:
     * 1. Opens with `->addArgument(` call
     * 2. Creates new Argument instance
     * 3. Chains setter calls for all properties (name, position, type, etc.)
     * 4. Conditionally includes default value if present
     * 5. Recursively generates code for all attributes
     * 6. Closes with appropriate parentheses
     *
     * Performance note: Array-based line accumulation is significantly more efficient
     * than string concatenation for large introspection outputs, as it avoids
     * quadratic time complexity from repeated string copies.
     *
     * @param array<int, string> &$lines Array to append generated code lines to (passed by reference)
     *
     * @return void
     *
     * @see SourceGenerator For the interface contract
     * @see Attribute::toSource() For nested attribute generation
     */
    public function toSource(array &$lines): void
    {
        $lines[] = '->addArgument(';
        $lines[] = '(new ' . static::class . '()';

        // Basic properties
        $lines[] = '    ->setName(' . var_export($this->getName(), true) . ')';
        $lines[] = '    ->setPosition(' . var_export($this->getPosition(), true) . ')';
        $lines[] = '    ->setVariadic(' . var_export($this->isVariadic(), true) . ')';
        $lines[] = '    ->setByReference(' . var_export($this->isByReference(), true) . ')';
        $lines[] = '    ->setType(' . var_export($this->getType(), true) . ')';
        $lines[] = '    ->setOptional(' . var_export($this->isOptional(), true) . ')';

        if (isset($this->defaultValue)) {
            $lines[] = '    ->setDefaultValue(' . var_export($this->getDefaultValue(), true) . ')';
        }

        $lines[] = ')';
        
        // Attributes
        foreach ($this->getAttributes() as $attribute) {
            $lines[] = '->addAttribute(';
            $attribute->toSource($lines);
            $lines[] = ')';
        }
        
        $lines[] = ')';
    }
}