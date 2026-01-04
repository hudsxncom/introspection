<?php

namespace Hudsxn\Introspection\Structures;

use Hudsxn\Introspection\Traits\CommonFields;
use Hudsxn\Introspection\Traits\HasArguments;
use ReflectionAttribute;

/**
 * Represents a PHP 8+ attribute with full introspection and code generation support.
 *
 * This class encapsulates PHP attributes (the modern replacement for docblock annotations),
 * providing a structured representation that can be introspected, manipulated, and
 * regenerated as source code. Attributes in PHP 8+ are defined using the `#[...]` syntax
 * and can be attached to classes, methods, properties, parameters, and constants.
 *
 * The class captures both the attribute's fully-qualified class name and its constructor
 * arguments, supporting both positional and named argument syntax. This enables complete
 * reconstruction of attribute declarations from reflection data.
 *
 * Key capabilities:
 * - Attribute name storage (fully-qualified class name)
 * - Constructor argument introspection (positional and named parameters)
 * - Bidirectional conversion between ReflectionAttribute and structured data
 * - PHP source code generation for caching and code generation tools
 * - Memory-efficient line-based output via array accumulation
 *
 * Example usage:
 * ```php
 * // Given: #[Route('/api/users', methods: ['GET', 'POST'])]
 * $attr = new Attribute();
 * $attr->from($reflectionAttribute);
 * // Now contains: name='Route', arguments=[0=>'/api/users', 'methods'=>['GET','POST']]
 * ```
 *
 * @package Hudsxn\Introspection\Structures
 *
 * @property-read string $name The fully-qualified class name of the attribute
 * @property-read array<int|string, Argument> $arguments Constructor arguments (indexed or named)
 *
 * @see ReflectionAttribute For the underlying reflection source
 * @see SourceGenerator For the code generation contract
 * @see HasArguments For argument management capabilities
 *
 * @author Hudsxn
 */
class Attribute
{
    use CommonFields;
    use HasArguments;

    /**
     * Populates this Attribute instance from a ReflectionAttribute object.
     *
     * This method extracts all metadata from PHP's attribute reflection system,
     * performing a comprehensive conversion that preserves both the attribute's
     * identity and its constructor arguments.
     *
     * Argument handling logic:
     * - Positional arguments: Stored with numeric keys (0, 1, 2, ...) representing position
     * - Named arguments: Stored with string keys matching the parameter names
     * - Mixed arguments: PHP allows mixing both styles; this method preserves the distinction
     *
     * The method creates Argument instances for each constructor parameter, storing:
     * - The argument name (parameter name for named args, stringified index for positional)
     * - The actual value passed to the attribute constructor
     * - The position (for positional arguments) or 0 (for named arguments)
     *
     * Note: Unlike method/function parameters, attribute arguments represent actual values
     * passed during instantiation, not parameter definitions. The setValue() method is used
     * instead of setDefaultValue() to reflect this semantic difference.
     *
     * @param ReflectionAttribute $attribute The reflection attribute to extract from
     *
     * @return static Returns the current instance for method chaining
     *
     * @see ReflectionAttribute::getName() For attribute class name extraction
     * @see ReflectionAttribute::getArguments() For constructor argument extraction
     */
    public function from(ReflectionAttribute $attribute): static
    {
        // -------------------- Name --------------------
        $this->setName($attribute->getName());

        // -------------------- Arguments --------------------
        foreach ($attribute->getArguments() as $key => $value) {
            $arg = new Argument();
            $arg->setName(is_string($key) ? $key : (string)$key)  // Use string key if available
                ->setValue($value)
                ->setPosition(is_int($key) ? $key : 0);          // Numeric keys get position

            $this->addArgument($arg);
        }

        return $this;
    }

    /**
     * Generates PHP source code lines representing this attribute definition.
     *
     * This method implements the SourceGenerator contract by appending code lines
     * to the provided array reference. The generated code creates a fluent chain
     * of method calls that reconstructs this Attribute instance with all its
     * constructor arguments.
     *
     * Generated code structure:
     * 1. Creates a new Attribute instance: `(new Attribute()`
     * 2. Sets the attribute name via `->setName(...)`
     * 3. Recursively generates code for each argument via Argument::toSource()
     * 4. Closes with parenthesis to complete the expression
     *
     * The output can be used for:
     * - Caching introspection results as executable PHP code
     * - Code generation tools that need to recreate attribute declarations
     * - Serialization systems that prefer code over data structures
     * - Build-time optimization where reflection overhead must be eliminated
     *
     * Performance considerations:
     * - Array-based line accumulation avoids O(n²) string concatenation complexity
     * - Each line is a separate array element, making join operations efficient
     * - Recursive argument generation maintains the same performance characteristics
     *
     * @param array<int, string> &$lines Array to append generated code lines to (passed by reference)
     *
     * @return void
     *
     * @see SourceGenerator For the interface contract
     * @see Argument::toSource() For nested argument code generation
     */
    public function toSource(array &$lines)
    {
        $lines[] = '(new ' . static::class . '()';

        $lines[] = '->setName(' . var_export($this->getName(), true) . ')';
        
        foreach($this->arguments as $argument)
        {
            $argument->toSource($lines);
        }

        $lines[] = ')';
    }

}