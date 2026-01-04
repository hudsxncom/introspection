<?php

namespace Hudsxn\Introspection\Traits;

/**
 * Trait UseTraits
 *
 * Provides functionality for managing PHP traits used by a class-like structure.
 * 
 * Allows adding, checking, retrieving, and generating PHP source code for traits.
 *
 * @property string[] $uses List of fully qualified trait class names.
 */
trait UseTraits
{
    /**
     * Array of fully qualified trait class names used by this structure.
     *
     * @var string[]
     */
    private array $uses = [];

    // ------------------ Add / Check ------------------

    /**
     * Add a trait to this structure.
     *
     * Fluent setter.
     *
     * @param string $className Fully qualified trait class name.
     * @return static Returns self for fluent chaining.
     */
    public function addTrait(string $className): static
    {
        $this->uses[] = $className;
        return $this;
    }

    /**
     * Check if a specific trait is used by this structure.
     *
     * @param string $className Fully qualified trait class name.
     * @return bool True if the trait is used, false otherwise.
     */
    public function usesTrait(string $className): bool
    {
        return in_array($className, $this->uses, true);
    }

    /**
     * Retrieve all traits used by this structure.
     *
     * @return string[] Array of fully qualified trait class names.
     */
    public function getTraits(): array
    {
        return $this->uses;
    }

    // ------------------ PHP Source Generation ------------------

    /**
     * Append PHP source code for all used traits to the provided lines array.
     *
     * Useful for code generation or introspection-based reconstruction.
     *
     * Example output for two traits:
     * ```
     * ->addTrait('App\Traits\FooTrait')
     * ->addTrait('App\Traits\BarTrait')
     * ```
     *
     * @param array<string> $lines Array of PHP source lines to append to.
     * @return void
     */
    public function appendUses(array &$lines): void
    {
        foreach ($this->uses as $line) {
            $lines[] = '->addTrait(' . var_export($line, true) . ')';
        }
    }
}
