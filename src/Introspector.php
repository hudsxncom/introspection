<?php

namespace Hudsxn\Introspection;

use Hudsxn\Introspection\Traits\Singleton;
use Hudsxn\Introspection\Structures\ClassObj;
use Hudsxn\Introspection\Structures\InterfaceObj;
use Hudsxn\Introspection\Structures\TraitObj;
use ReflectionClass;

/**
 * Class Introspector
 *
 * Singleton loader and introspection utility for classes, interfaces, and traits.
 * 
 * Features:
 * - **L3 caching**: memory + filesystem
 * - **Lazy loading**: only loads when requested
 * - **Refresh modes**: full refresh or selective refresh
 * - **Type-safe loader** using integer constants
 * - Generates PHP code for caching and runtime hydration
 *
 * Example usage:
 * ```php
 * $introspector = Introspector::getInstance();
 * $introspector->init(__DIR__ . '/cache');
 * 
 * // Fetch a class with caching
 * $class = $introspector->getClass(MyApp\Foo::class);
 *
 * // Refresh a specific class in cache
 * $introspector->setMode([MyApp\Foo::class]);
 * $class = $introspector->getClass(MyApp\Foo::class);
 *
 * // Clear cache for all classes
 * $introspector->clearCache();
 * ```
 *
 * @package Hudsxn\Introspection
 */
class Introspector
{
    use Singleton;

    /** -------------------- Type Constants -------------------- */

    /**
     * Type constant for classes
     */
    public const TYPE_CLASS = 1;

    /**
     * Type constant for interfaces
     */
    public const TYPE_INTERFACE = 2;

    /**
     * Type constant for traits
     */
    public const TYPE_TRAIT = 3;

    /** -------------------- Cache Modes -------------------- */

    /**
     * Cache mode for fastest memory + filesystem usage
     */
    public const MODE_FASTEST = 'fastest';

    /**
     * Cache mode for regenerating all or targeted classes
     */
    public const MODE_REFRESH = 'refresh';

    /** -------------------- Properties -------------------- */

    /**
     * Directory for filesystem cache storage
     * @var string
     */
    private string $cacheDir;

    /**
     * Current cache mode. Can be:
     * - string: 'fastest' | 'refresh'
     * - array<string>: list of FQCNs to refresh
     * @var string|array<string>
     */
    private string|array $mode = self::MODE_FASTEST;

    /**
     * Memory cache of loaded class/interface/trait objects
     * @var array<string, ClassObj|InterfaceObj|TraitObj>
     */
    private array $memoryCache = [];

    /** -------------------- Public API -------------------- */

    /**
     * Initialize the introspector.
     *
     * Creates the cache directory if it does not exist.
     *
     * @param string|null $cacheDir Optional filesystem cache directory. Defaults to `__DIR__ . '/cache'`
     * @return void
     */
    public function init(?string $cacheDir = null): void
    {
        $this->cacheDir = rtrim($cacheDir ?? __DIR__ . '/cache', '/');
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function clearInstanceCache()
    {
        $this->memoryCache = [];
    }

    /**
     * Set the caching mode.
     *
     * Modes:
     * - `MODE_FASTEST` (default) → memory + filesystem cache
     * - `MODE_REFRESH` → regenerate all classes
     * - array of FQCNs → regenerate only specified classes
     *
     * @param string|array<string> $mode
     * @return static Fluent self-reference
     */
    public function setMode(string|array $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get a class object by fully-qualified class name (FQCN)
     *
     * @param string $fqcn Fully-qualified class name
     * @return ClassObj
     */
    public function getClass(string $fqcn): ClassObj
    {
        return $this->get(self::TYPE_CLASS, $fqcn);
    }

    /**
     * Get an interface object by fully-qualified interface name (FQIN)
     *
     * @param string $fqcn Fully-qualified interface name
     * @return InterfaceObj
     */
    public function getInterface(string $fqcn): InterfaceObj
    {
        return $this->get(self::TYPE_INTERFACE, $fqcn);
    }

    /**
     * Get a trait object by fully-qualified trait name (FQTN)
     *
     * @param string $fqcn Fully-qualified trait name
     * @return TraitObj
     */
    public function getTrait(string $fqcn): TraitObj
    {
        return $this->get(self::TYPE_TRAIT, $fqcn);
    }

    /**
     * Clear cache from memory and/or filesystem.
     *
     * @param string|null $fqcn Fully-qualified name to clear, or null to clear all cached items
     * @return void
     */
    public function clearCache(?string $fqcn = null): void
    {
        if ($fqcn === null) {
            foreach (glob($this->cacheDir . '/*.php') as $file) @unlink($file);
            $this->memoryCache = [];
            return;
        }

        $cacheFile = $this->getCacheFilePath($fqcn);
        if (file_exists($cacheFile)) @unlink($cacheFile);

        unset($this->memoryCache[$fqcn]);
    }

    /** -------------------- Internal Loader -------------------- */

    /**
     * Internal loader with tiered caching: memory → filesystem → reflection
     *
     * @param int $type One of TYPE_CLASS, TYPE_INTERFACE, TYPE_TRAIT
     * @param string $fqcn Fully-qualified class/interface/trait name
     * @return ClassObj|InterfaceObj|TraitObj
     * @throws \InvalidArgumentException
     */
    private function get(int $type, string $fqcn): ClassObj|InterfaceObj|TraitObj
    {
        $needsRefresh = match (true) {
            is_string($this->mode) && $this->mode === self::MODE_REFRESH => true,
            is_array($this->mode) && in_array($fqcn, $this->mode) => true,
            default => false,
        };

        // 1. Memory cache
        if (isset($this->memoryCache[$fqcn]) && !$needsRefresh) {
            return $this->memoryCache[$fqcn];
        }

        // 2. Filesystem cache
        $cacheFile = $this->getCacheFilePath($fqcn);
        if (!$needsRefresh && file_exists($cacheFile)) {
            return $this->memoryCache[$fqcn] = include $cacheFile;
        }

        // 3. Reflection fallback
        $obj = match ($type) {
            self::TYPE_CLASS => $this->reflectClass($fqcn),
            self::TYPE_INTERFACE => $this->reflectInterface($fqcn),
            self::TYPE_TRAIT => $this->reflectTrait($fqcn),
            default => throw new \InvalidArgumentException("Unknown type: $type"),
        };

        // Generate cache content
        $lines = ['<?php return '];
        $obj->toSource($lines);
        $lines[] = ';';

        // Write cache atomically
        $tmpFile = $cacheFile . '.tmp';
        file_put_contents($tmpFile, implode('', $lines));
        rename($tmpFile, $cacheFile);

        // Store in memory cache
        return $this->memoryCache[$fqcn] = $obj;
    }

    /**
     * Build the cache file path for a FQCN
     *
     * @param string $fqcn
     * @return string
     */
    private function getCacheFilePath(string $fqcn): string
    {
        return $this->cacheDir . '/' . sha1($fqcn) . '.php';
    }

    /** -------------------- Reflection Helpers -------------------- */

    private function reflectClass(string $fqcn): ClassObj
    {
        return (new ClassObj())->from(new ReflectionClass($fqcn));
    }

    private function reflectInterface(string $fqcn): InterfaceObj
    {
        return (new InterfaceObj())->from(new ReflectionClass($fqcn));
    }

    private function reflectTrait(string $fqcn): TraitObj
    {
        return (new TraitObj())->from(new ReflectionClass($fqcn));
    }
}
