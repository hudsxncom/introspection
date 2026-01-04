<?php

namespace Hudsxn\Introspection\Traits;

/**
 * Trait Typed
 *
 * Provides functionality for structures that have type information, default values,
 * optionality, and by-reference flags. Commonly used for method arguments, properties,
 * or other typed elements.
 *
 * Includes fluent setters, getters, and a helper for generating PHP source code.
 *
 * @property mixed       $value        Current value of the element.
 * @property string|null $type         Type hint of the element, e.g., 'int', 'string', 'MyClass'.
 * @property bool        $isOptional   Whether the element is optional.
 * @property mixed       $defaultValue The default value if any.
 * @property bool        $isByReference Whether the element is passed by reference.
 */
trait Typed
{
    /**
     * The current value of the element.
     *
     * @var mixed
     */
    private mixed $value = null;

    /**
     * Type hint of the element (nullable).
     *
     * @var string|null
     */
    private ?string $type = null;

    /**
     * Whether the element is optional.
     *
     * @var bool
     */
    private bool $isOptional = false;

    /**
     * Default value of the element.
     *
     * @var mixed
     */
    private mixed $defaultValue = null;

    /**
     * Whether the element is passed by reference.
     *
     * @var bool
     */
    private bool $isByReference = false;

    // ------------------ Value ------------------

    /**
     * Get the current value of the element.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Set the current value of the element.
     *
     * @param mixed $value The value to set.
     * @return static Fluent self reference.
     */
    public function setValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    // ------------------ Type ------------------

    /**
     * Get the type hint of the element.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Set the type hint of the element.
     *
     * @param string|null $type Type hint, e.g., 'int', 'string', or class name.
     * @return static Fluent self reference.
     */
    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    // ------------------ Optional / Default ------------------

    /**
     * Whether the element is optional.
     *
     * @return bool
     */
    public function isOptional(): bool
    {
        return $this->isOptional;
    }

    /**
     * Set whether the element is optional.
     *
     * @param bool $optional True if optional.
     * @return static Fluent self reference.
     */
    public function setOptional(bool $optional): static
    {
        $this->isOptional = $optional;
        return $this;
    }

    /**
     * Get the default value of the element.
     *
     * @return mixed
     */
    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    /**
     * Set the default value of the element.
     *
     * @param mixed $defaultValue Default value.
     * @return static Fluent self reference.
     */
    public function setDefaultValue(mixed $defaultValue): static
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    // ------------------ By Reference ------------------

    /**
     * Whether the element is passed by reference.
     *
     * @return bool
     */
    public function isByReference(): bool
    {
        return $this->isByReference;
    }

    /**
     * Set whether the element is passed by reference.
     *
     * @param bool $byReference True if by reference.
     * @return static Fluent self reference.
     */
    public function setByReference(bool $byReference): static
    {
        $this->isByReference = $byReference;
        return $this;
    }

    // ------------------ PHP Source Generation ------------------

    /**
     * Append PHP source code representing the typed element to the provided array of lines.
     *
     * This is useful for regenerating code or introspection-based code generation.
     *
     * Example output:
     * ```
     * ->setValue(123)
     * ->setType('int')
     * ->setOptional(false)
     * ->setDefaultValue(null)
     * ->setByReference(false)
     * ```
     *
     * @param array<string> $lines Array to append PHP source code lines to.
     * @return void
     */
    public function addTypedSource(array &$lines): void
    {
        $lines[] = '->setValue(' . var_export($this->value, true) . ')';
        $lines[] = '->setType(' . var_export($this->type, true) . ')';
        $lines[] = '->setOptional(' . var_export($this->isOptional, true) . ')';
        $lines[] = '->setDefaultValue(' . var_export($this->defaultValue, true) . ')';
        $lines[] = '->setByReference(' . var_export($this->isByReference, true) . ')';
    }
}
