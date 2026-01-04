<?php

namespace Hudsxn\Introspection\Traits;

/**
 * Trait ExtendsSomething
 *
 * Provides functionality to manage an "extends" relationship for class-like structures.
 * Useful for representing inheritance in classes during introspection or code generation.
 *
 * This trait allows setting, retrieving, and generating PHP source for the parent class.
 *
 * @property string|null $extends The fully qualified name of the parent class, or null if none
 */
trait ExtendsSomething
{
    /**
     * The fully qualified name of the parent class, or null if no parent.
     *
     * @var string|null
     */
    private ?string $extends = null;

    /**
     * Set the parent class for this structure.
     *
     * Fluent setter that allows method chaining.
     *
     * @param string|null $extends Fully qualified parent class name, or null to unset.
     * @return static Returns self for fluent chaining.
     */
    public function setExtends(?string $extends): static
    {
        $this->extends = $extends;
        return $this;
    }

    /**
     * Get the parent class for this structure.
     *
     * @return string|null Fully qualified parent class name, or null if none.
     */
    public function getExtends(): ?string
    {
        return $this->extends ?? null;
    }

    /**
     * Append the "extends" declaration to a PHP source lines array.
     *
     * Intended for code generation purposes. Only appends if a parent class exists.
     *
     * Example output:
     * ```
     * ->setExtends('ParentClassName')
     * ```
     *
     * @param array<string> $lines Array of lines to append PHP source code to.
     * @return void
     */
    public function appendExtends(array &$lines): void
    {
        if (isset($this->extends) && $this->extends !== null) {
            $lines[] = '->setExtends(' . var_export($this->extends, true) . ')';
        }
    }
}
