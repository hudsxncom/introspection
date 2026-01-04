<?php

namespace Hudsxn\Introspection\Traits;

/**
 * Trait Modifiers
 *
 * Provides a memory-efficient way to store and manage PHP modifiers using a bitmask.
 * Each modifier (public, protected, private, static, final, abstract, readonly) 
 * is stored as a single bit in an integer, minimizing memory usage.
 *
 * This trait includes:
 * - Fluent setters and boolean getters for each modifier.
 * - Internal bitmask handling.
 * - PHP source generation via `appendModifiers`.
 *
 * @property int $modifiers The bitmask storing all modifiers
 */
trait Modifiers
{
    /**
     * Bitmask of all modifiers.
     * Each bit represents a boolean flag (0 = false, 1 = true).
     *
     * @var int
     */
    private int $modifiers = 0;

    // ------------------ Modifier bit constants ------------------
    private const PUBLIC    = 1 << 0; // 0000_0001
    private const PROTECTED = 1 << 1; // 0000_0010
    private const PRIVATE   = 1 << 2; // 0000_0100
    private const STATIC    = 1 << 3; // 0000_1000
    private const FINAL     = 1 << 4; // 0001_0000
    private const ABSTRACT  = 1 << 5; // 0010_0000
    private const READONLY  = 1 << 6; // 0100_0000

    // ------------------ Setters (fluent) ------------------

    public function setPublic(bool $value = true): static
    {
        $this->setModifier(self::PUBLIC, $value);
        return $this;
    }

    public function setProtected(bool $value = true): static
    {
        $this->setModifier(self::PROTECTED, $value);
        return $this;
    }

    public function setPrivate(bool $value = true): static
    {
        $this->setModifier(self::PRIVATE, $value);
        return $this;
    }

    public function setStatic(bool $value = true): static
    {
        $this->setModifier(self::STATIC, $value);
        return $this;
    }

    public function setFinal(bool $value = true): static
    {
        $this->setModifier(self::FINAL, $value);
        return $this;
    }

    public function setAbstract(bool $value = true): static
    {
        $this->setModifier(self::ABSTRACT, $value);
        return $this;
    }

    public function setReadOnly(bool $value = true): static
    {
        $this->setModifier(self::READONLY, $value);
        return $this;
    }

    // ------------------ Getters (boolean) ------------------

    public function isPublic(): bool
    {
        return $this->hasModifier(self::PUBLIC);
    }

    public function isProtected(): bool
    {
        return $this->hasModifier(self::PROTECTED);
    }

    public function isPrivate(): bool
    {
        return $this->hasModifier(self::PRIVATE);
    }

    public function isStatic(): bool
    {
        return $this->hasModifier(self::STATIC);
    }

    public function isFinal(): bool
    {
        return $this->hasModifier(self::FINAL);
    }

    public function isAbstract(): bool
    {
        return $this->hasModifier(self::ABSTRACT);
    }

    public function isReadOnly(): bool
    {
        return $this->hasModifier(self::READONLY);
    }

    // ------------------ Internal helper methods ------------------

    /**
     * Set or clear a modifier bit in the bitmask.
     *
     * @param int  $modifierBit Bitmask for the modifier to change
     * @param bool $value       True to set the modifier, false to clear it
     * @return void
     */
    private function setModifier(int $modifierBit, bool $value): void
    {
        if ($value) {
            $this->modifiers |= $modifierBit;  // Set the bit
        } else {
            $this->modifiers &= ~$modifierBit; // Clear the bit
        }
    }

    /**
     * Check if a specific modifier is set.
     *
     * @param int $modifierBit Bitmask of the modifier to check
     * @return bool True if set, false otherwise
     */
    private function hasModifier(int $modifierBit): bool
    {
        return ($this->modifiers & $modifierBit) !== 0;
    }

    // ------------------ Bitmask access ------------------

    /**
     * Get the raw modifier bitmask.
     *
     * @return int Bitmask representing all modifiers.
     */
    public function getModifiers(): int
    {
        return $this->modifiers;
    }

    /**
     * Set the raw modifier bitmask.
     *
     * @param int $bitmask The new bitmask for all modifiers.
     * @return static Returns self for fluent chaining.
     */
    public function setModifiers(int $bitmask): static
    {
        $this->modifiers = $bitmask;
        return $this;
    }

    // ------------------ PHP Source Generation ------------------

    /**
     * Append PHP source code representing active modifiers to the provided array of lines.
     *
     * Example output:
     * ```
     * ->setPublic(true)
     * ->setStatic(true)
     * ->setReadOnly(true)
     * ```
     *
     * @param array<string> $lines Array of lines to append generated PHP source to.
     * @return void
     */
    public function appendModifiers(array &$lines): void
    {
        $lines[] = '';

        if ($this->isPublic()) {
            $lines[] = '->setPublic(true)';
        }
        if ($this->isProtected()) {
            $lines[] = '->setProtected(true)';
        }
        if ($this->isPrivate()) {
            $lines[] = '->setPrivate(true)';
        }
        if ($this->isStatic()) {
            $lines[] = '->setStatic(true)';
        }
        if ($this->isFinal()) {
            $lines[] = '->setFinal(true)';
        }
        if ($this->isAbstract()) {
            $lines[] = '->setAbstract(true)';
        }
        if ($this->isReadOnly()) {
            $lines[] = '->setReadOnly(true)';
        }
        $lines[] = '';
    }
}
