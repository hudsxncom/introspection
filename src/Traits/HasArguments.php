<?php

namespace Hudsxn\Introspection\Traits;

use Hudsxn\Introspection\Structures\Argument;

/**
 * Trait HasArguments
 *
 * Provides common functionality for handling arguments in class-like structures
 * such as Attributes or Methods.
 *
 * This trait ensures consistency in managing arguments, allowing you to:
 * - Add, retrieve, and check arguments by name.
 * - Count arguments.
 * - Generate PHP source for all arguments.
 *
 * @property Argument[] $arguments List of attached Argument objects, indexed by name
 */
trait HasArguments
{
    /**
     * @var Argument[] List of arguments attached to this structure, keyed by argument name
     */
    private array $arguments = [];

    /**
     * Get the total number of arguments.
     *
     * @return int The count of arguments attached to this structure.
     */
    public function argumentCount(): int
    {
        return \count($this->arguments);
    }

    /**
     * Check if an argument exists by name.
     *
     * @param string $name The name of the argument.
     * @return bool True if an argument with the given name exists, false otherwise.
     */
    public function hasArgument(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    /**
     * Get an argument by name.
     *
     * @param string $name The name of the argument.
     * @return Argument|null The Argument instance if it exists, null otherwise.
     */
    public function getArgument(string $name): ?Argument
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * Add or replace an argument.
     *
     * @param Argument $argument The argument to add.
     * @return static Returns self for fluent method chaining.
     */
    public function addArgument(Argument $argument): static
    {
        $this->arguments[$argument->getName()] = $argument;
        return $this;
    }

    /**
     * Retrieve all arguments as a numeric array.
     *
     * @return Argument[] Numeric array of all Argument objects.
     */
    public function getArguments(): array
    {
        return array_values($this->arguments);
    }
}
