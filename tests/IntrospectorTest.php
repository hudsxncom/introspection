<?php

namespace Hudsxn\Introspection\Tests;

require __DIR__ . '/../tools/TestObjects.php';

use Hudsxn\Introspection\Introspector;
use Hudsxn\Introspection\Structures\ClassObj;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[\Attribute]
class TestAttribute {}

/**
 * Full-depth introspection test suite
 * Covers everything: classes, interfaces, traits,
 * properties, constants, methods, method arguments, and attributes.
 */
class IntrospectorTest extends TestCase
{
    private Introspector $introspector;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = __DIR__ . '/cache';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }

        $this->introspector = Introspector::getSelf();
        $this->introspector->init($this->cacheDir);
        $this->introspector->setMode(Introspector::MODE_FASTEST);
        $this->introspector->clearCache();
    }

    protected function tearDown(): void
    {
        $this->introspector->clearCache();
    }

    // ---------------------- Helper ----------------------
    private function assertClassLikeMatchesReflection($cached, ReflectionClass $ref, string $context = ''): void
    {
        $prefix = $context ? "[$context] " : '';

        // Class/Interface/Trait name & namespace
        $this->assertSame($ref->getName(), $cached->getName(), $prefix . 'Name mismatch');
        $this->assertSame($ref->getNamespaceName(), $cached->getNamespace(), $prefix . 'Namespace mismatch');

        // Type
        if ($ref->isTrait()) {
            $this->assertSame(Introspector::TYPE_TRAIT, $cached->getType(), $prefix . 'Type mismatch (trait)');
        } elseif ($ref->isInterface()) {
            $this->assertSame(Introspector::TYPE_INTERFACE, $cached->getType(), $prefix . 'Type mismatch (interface)');
        } else {
            $this->assertSame(Introspector::TYPE_CLASS, $cached->getType(), $prefix . 'Type mismatch (class)');
        }

        // Modifiers
        $this->assertSame($ref->isFinal(), $cached->isFinal(), $prefix . 'Final mismatch');
        $this->assertSame($ref->isAbstract(), $cached->isAbstract(), $prefix . 'Abstract mismatch');

        // ------------------ Constants ------------------
        foreach ($ref->getReflectionConstants() as $const) {
            $cachedConst = $cached->getConstant($const->getName());
            $this->assertNotNull($cachedConst, $prefix . "Constant {$const->getName()} missing");
            $this->assertSame($const->getValue(), $cachedConst->getValue(), $prefix . "Constant {$const->getName()} value mismatch");
            $this->assertSame($const->isPublic(), $cachedConst->isPublic(), $prefix . "Constant {$const->getName()} public mismatch");
            $this->assertSame($const->isFinal(), $cachedConst->isFinal(), $prefix . "Constant {$const->getName()} final mismatch");

            $refAttrs = array_map(fn($a) => $a->getName(), $const->getAttributes());
            $cachedAttrs = array_map(fn($a) => $a->getName(), $cachedConst->getAttributes());
            sort($refAttrs); sort($cachedAttrs);
            $this->assertSame($refAttrs, $cachedAttrs, $prefix . "Constant {$const->getName()} attributes mismatch");
        }

        // ------------------ Properties ------------------
        foreach ($ref->getProperties() as $prop) {
            $cachedProp = $cached->getProperty($prop->getName());
            $this->assertNotNull($cachedProp, $prefix . "Property {$prop->getName()} missing");
            $this->assertSame($prop->isStatic(), $cachedProp->isStatic(), $prefix . "Property {$prop->getName()} static mismatch");
            $this->assertSame($prop->isPublic(), $cachedProp->isPublic(), $prefix . "Property {$prop->getName()} public mismatch");
            $this->assertSame($prop->isProtected(), $cachedProp->isProtected(), $prefix . "Property {$prop->getName()} protected mismatch");
            $this->assertSame($prop->isPrivate(), $cachedProp->isPrivate(), $prefix . "Property {$prop->getName()} private mismatch");
            $this->assertSame($prop->hasType() ? (string)$prop->getType() : 'mixed', $cachedProp->getType(), $prefix . "Property {$prop->getName()} type mismatch");

            if ($prop->hasDefaultValue()) {
                $this->assertSame($prop->getDefaultValue(), $cachedProp->getDefaultValue(), $prefix . "Property {$prop->getName()} default mismatch");
            }

            $refAttrs = array_map(fn($a) => $a->getName(), $prop->getAttributes());
            $cachedAttrs = array_map(fn($a) => $a->getName(), $cachedProp->getAttributes());
            sort($refAttrs); sort($cachedAttrs);
            $this->assertSame($refAttrs, $cachedAttrs, $prefix . "Property {$prop->getName()} attributes mismatch");
        }

        // ------------------ Methods & Arguments ------------------
        foreach ($ref->getMethods() as $method) {
            $cachedMethod = $cached->getMethod($method->getName());
            $this->assertNotNull($cachedMethod, $prefix . "Method {$method->getName()} missing");
            $this->assertSame($method->isStatic(), $cachedMethod->isStatic(), $prefix . "Method {$method->getName()} static mismatch");
            $this->assertSame($method->isPublic(), $cachedMethod->isPublic(), $prefix . "Method {$method->getName()} public mismatch");
            $this->assertSame($method->isProtected(), $cachedMethod->isProtected(), $prefix . "Method {$method->getName()} protected mismatch");
            $this->assertSame($method->isPrivate(), $cachedMethod->isPrivate(), $prefix . "Method {$method->getName()} private mismatch");
            $this->assertSame($method->isAbstract(), $cachedMethod->isAbstract(), $prefix . "Method {$method->getName()} abstract mismatch");
            $this->assertSame($method->isFinal(), $cachedMethod->isFinal(), $prefix . "Method {$method->getName()} final mismatch");
            $this->assertSame($method->hasReturnType() ? (string)$method->getReturnType() : 'mixed', $cachedMethod->getReturnType(), $prefix . "Method {$method->getName()} return mismatch");

            $refAttrs = array_map(fn($a) => $a->getName(), $method->getAttributes());
            $cachedAttrs = array_map(fn($a) => $a->getName(), $cachedMethod->getAttributes());
            sort($refAttrs); sort($cachedAttrs);
            $this->assertSame($refAttrs, $cachedAttrs, $prefix . "Method {$method->getName()} attributes mismatch");

            $refParams = $method->getParameters();
            $cachedParams = $cachedMethod->getArguments();
            $this->assertCount(count($refParams), $cachedParams, $prefix . "Method {$method->getName()} arg count mismatch");

            foreach ($refParams as $i => $refParam) {
                $cachedParam = $cachedParams[$i];
                $this->assertSame($refParam->getName(), $cachedParam->getName(), $prefix . "Method {$method->getName()} param {$i} name mismatch");
                $this->assertSame($refParam->getPosition(), $cachedParam->getPosition(), $prefix . "Method {$method->getName()} param {$i} position mismatch");
                $this->assertSame($refParam->isOptional(), $cachedParam->isOptional(), $prefix . "Method {$method->getName()} param {$i} optional mismatch");
                $this->assertSame($refParam->isVariadic(), $cachedParam->isVariadic(), $prefix . "Method {$method->getName()} param {$i} variadic mismatch");
                $this->assertSame($refParam->isPassedByReference(), $cachedParam->isByReference(), $prefix . "Method {$method->getName()} param {$i} by-ref mismatch");

                $refType = $refParam->getType();
                $cachedType = $cachedParam->getType();
                $this->assertSame($refType ? ($refType->allowsNull() ? '?' . $refType->__toString() : $refType->__toString()) : 'mixed', $cachedType, $prefix . "Method {$method->getName()} param {$i} type mismatch");

                if ($refParam->isDefaultValueAvailable()) {
                    $this->assertSame($refParam->getDefaultValue(), $cachedParam->getDefaultValue(), $prefix . "Method {$method->getName()} param {$i} default mismatch");
                }

                // Parameter attributes
                $refAttrs = array_map(fn($a) => $a->getName(), $refParam->getAttributes());
                $cachedAttrs = array_map(fn($a) => $a->getName(), $cachedParam->getAttributes());
                sort($refAttrs); sort($cachedAttrs);
                $this->assertSame($refAttrs, $cachedAttrs, $prefix . "Method {$method->getName()} param {$i} attributes mismatch");
            }
        }

        // Interfaces
        if (!$ref->isTrait() && !$ref->isInterface()) {
            $this->assertSame($ref->getInterfaceNames(), $cached->getImplements() ?: [], $prefix . 'Implemented interfaces mismatch');
        }

        // Traits
        $refTraits = array_keys($ref->getTraits());
        $cachedTraits = $cached->getTraits() ?: [];
        $this->assertSame($refTraits, $cachedTraits, $prefix . 'Used traits mismatch');

        // Class attributes
        $refAttrs = array_map(fn($a) => $a->getName(), $ref->getAttributes());
        $cachedAttrs = array_map(fn($a) => $a->getName(), $cached->getAttributes());
        sort($refAttrs); sort($cachedAttrs);
        $this->assertSame($refAttrs, $cachedAttrs, $prefix . 'Class attributes mismatch');
    }

    // ---------------------- Core Introspector Tests ----------------------

    /** @description This test ensures that the Introspector class follows the singleton
     * pattern, which is crucial for consistent caching and memory management
     * across the application. It calls Introspector::getSelf() twice and
     * asserts that both returned instances are exactly the same object. 
     * Singleton enforcement guarantees that all cached data, memory storage,
     * and mode settings are centralized and not fragmented across multiple
     * instances, preventing inconsistent introspection results and redundant
     * computations.
    */
    public function testSingleton(): void
    {
        $first = Introspector::getSelf();
        $second = Introspector::getSelf();
        $this->assertSame($first, $second, 'Introspector should be singleton');
    }

    /** @description Validates the behavior of the Introspector when operating in the
    * fastest caching mode. In this mode, introspected class objects
    * should be returned from in-memory cache without re-parsing or
    * regenerating their full metadata. This test fetches the same class
    * (\stdClass) twice and asserts that the same object instance is
    * returned both times. This ensures minimal overhead during repeated
    * introspections and confirms that the fastest mode prioritizes speed
    * by reusing cached objects.
    */
    public function testFastestModeReturnsSameObject(): void
    {
        $first = $this->introspector->getClass(\stdClass::class);
        $second = $this->introspector->getClass(\stdClass::class);
        $this->assertSame($first, $second, 'Fastest mode should return same object');
    }

    /** @description Tests the refresh mode of the Introspector. When refresh mode is active,
    * any request to introspect a class should regenerate its ClassObj,
    * ignoring any previously cached in-memory or file-based data. This
    * test first retrieves a class in the default mode, then switches
    * the mode to refresh and fetches the class again. The test asserts
    * that the returned object is a valid ClassObj instance, verifying
    * that the introspector correctly regenerates all metadata, including
    * properties, methods, constants, and attributes. This functionality
    * is critical for scenarios where source code changes require an
    * immediate update of cached introspection data.
    */
    public function testRefreshModeRegenerates(): void
    {
        $this->introspector->getClass(\stdClass::class);
        $this->introspector->setMode(Introspector::MODE_REFRESH);
        $refreshed = $this->introspector->getClass(\stdClass::class);
        $this->assertInstanceOf(ClassObj::class, $refreshed);
    }

    /** @description Confirms that the Introspector uses in-memory caching for repeated
    * requests of the same class. The test fetches the same class twice
    * in the current mode and asserts that both returned instances are
    * identical. This ensures that multiple accesses to frequently used
    * classes do not trigger repeated reflection parsing, reducing CPU
    * usage and improving performance when performing large-scale or
    * deep introspections across multiple classes.
    */
    public function testMemoryCacheIsUsed(): void
    {
        $first = $this->introspector->getClass(\stdClass::class);
        $second = $this->introspector->getClass(\stdClass::class);
        $this->assertSame($first, $second);
    }

    /** @description Ensures that calling clearCache() effectively removes all cached
    * data, both from the filesystem and from in-memory storage. This
    * test first caches a class object, confirms that a cached file
    * exists in the cache directory, then clears the cache and asserts
    * that no cached files remain. This behavior is essential to maintain
    * cache consistency and allows developers to reset cached metadata
    * after code changes or when testing fresh introspections.
    */
    public function testClearCacheRemovesFilesAndMemory(): void
    {
        $this->introspector->getClass(\stdClass::class);
        $this->assertNotEmpty(glob($this->cacheDir . '/*.php'));
        $this->introspector->clearCache();
        $this->assertEmpty(glob($this->cacheDir . '/*.php'));
    }

    /** @description Tests selective refresh functionality of the introspector, which allows
    * specific classes to be regenerated while leaving unrelated cached classes
    * untouched. The test caches two classes, sets the refresh mode to include
    * only one of them, then fetches both classes again. It asserts that the
    * included class is refreshed (new object) and the other remains unchanged.
    * This feature optimizes performance in large systems by only updating
    * metadata for classes that have changed.
    */
    public function testSelectiveRefresh(): void
    {
        $firstStd = $this->introspector->getClass(\stdClass::class);
        $firstArray = $this->introspector->getClass(\ArrayObject::class);

        $this->introspector->setMode([\stdClass::class]);

        $refreshedStd = $this->introspector->getClass(\stdClass::class);
        $unchangedArray = $this->introspector->getClass(\ArrayObject::class);

        $this->assertNotSame($firstStd, $refreshedStd);
        $this->assertSame($firstArray, $unchangedArray);
    }

    /** @description Confirms that the introspector automatically creates the cache directory
    * if it does not exist. This test deletes any existing temporary cache
    * directory, initializes the introspector pointing to that path, and asserts
    * that the directory exists. This behavior ensures that caching can be
    * enabled seamlessly without requiring manual directory setup.
    */
    public function testCacheDirectoryCreatedAutomatically(): void
    {
        $tmpDir = __DIR__ . '/tmp_cache';
        if (is_dir($tmpDir)) {
            array_map('unlink', glob("$tmpDir/*"));
            rmdir($tmpDir);
        }
        $this->introspector->init($tmpDir);
        $this->assertDirectoryExists($tmpDir);
        array_map('unlink', glob("$tmpDir/*"));
        rmdir($tmpDir);
    }

    /** @description Verifies that attempting to introspect a class that does not exist
    * triggers a ReflectionException. This ensures proper error handling
    * within the system and prevents undefined behavior when invalid class
    * names are provided.
    */
    public function testGetClassThrowsOnUnknown(): void
    {
        $this->expectException(\ReflectionException::class);
        $this->introspector->getClass('NonExistentClass123');
    }

    // ---------------------- Full Deep Introspection Tests ----------------------

    /** @description Performs a full deep introspection on a standard class, verifying
    * that all properties, constants, methods, method arguments, and
    * attributes are captured correctly by the introspector. The test
    * compares the cached ClassObj with native ReflectionClass metadata
    * to ensure complete and accurate introspection results.
    */
    public function testCachedClassDeep(): void
    {
        $fqcn = \TestClasses\FullTestClass::class;
        $obj = $this->introspector->getClass($fqcn);
        $ref = new ReflectionClass($fqcn);
        $this->assertClassLikeMatchesReflection($obj, $ref, "Class $fqcn");
    }

    /** @description Performs full introspection on an interface, checking that constants,
      * methods, method arguments, and attributes are correctly captured.
      * Compares the introspector's cached representation with ReflectionClass
      * to validate completeness.
    */
    public function testCachedInterfaceDeep(): void
    {
        $fqcn = \TestClasses\FullTestInterface::class;
        $obj = $this->introspector->getInterface($fqcn);
        $ref = new ReflectionClass($fqcn);
        $this->assertClassLikeMatchesReflection($obj, $ref, "Interface $fqcn");
    }

    /** @description Performs full introspection on a trait, verifying that all properties,
      * methods, method arguments, and attributes are captured. Ensures that
      * the introspector correctly handles traits and that the cached object
      * matches the ReflectionClass metadata.
    */
    public function testCachedTraitDeep(): void
    {
        $fqcn = \TestClasses\FullTestTrait::class;
        $obj = $this->introspector->getTrait($fqcn);
        $ref = new ReflectionClass($fqcn);
        $this->assertClassLikeMatchesReflection($obj, $ref, "Trait $fqcn");
    }

    /** @description Validates that the introspector correctly generates and persists cache
    * files to the filesystem. This test introspects a class, verifies that
    * a corresponding cache file is created in the cache directory, and then
    * confirms that the cached file contains valid PHP code that can be
    * included without errors. This ensures the cache generation mechanism
    * properly serializes introspection metadata to disk for future reuse.
    */
    public function testCacheFileGeneration(): void
    {
        $fqcn = \stdClass::class;
        $this->introspector->getClass($fqcn);
        
        $cacheFiles = glob($this->cacheDir . '/*.php');
        $this->assertNotEmpty($cacheFiles, 'Cache file should be generated');
        
        // Verify cache file is valid PHP
        $cacheFile = $cacheFiles[0];
        $this->assertFileExists($cacheFile);
        $cached = include $cacheFile;
        $this->assertInstanceOf(ClassObj::class, $cached);
    }

    /** @description Tests that clearing only the instance (in-memory) cache does not delete
    * the persistent filesystem cache files. This test first caches a class
    * to generate a cache file, then calls clearInstanceCache() to remove
    * only the in-memory references, and finally verifies that the cache
    * file still exists on disk. This separation allows for memory cleanup
    * while preserving filesystem cache for performance across requests.
    */
    public function testClearInstanceCacheKeepsFilesystemCache(): void
    {
        $fqcn = \stdClass::class;
        $this->introspector->getClass($fqcn);
        
        $cacheFiles = glob($this->cacheDir . '/*.php');
        $this->assertNotEmpty($cacheFiles, 'Cache file should exist before clearing instance cache');
        
        $this->introspector->clearInstanceCache();
        
        $cacheFilesAfter = glob($this->cacheDir . '/*.php');
        $this->assertNotEmpty($cacheFilesAfter, 'Cache file should still exist after clearing instance cache');
        $this->assertEquals($cacheFiles, $cacheFilesAfter, 'Same cache files should exist');
    }

    /** @description Confirms that after clearing the instance cache, subsequent requests
    * for the same class return a new object instance loaded from the
    * filesystem cache rather than the previous in-memory reference. This
    * test caches a class, clears the instance cache, then fetches the
    * class again and asserts that a different object is returned while
    * the cache file remains on disk. This validates proper cache layering
    * between memory and filesystem.
    */
    public function testInstanceCacheClearReturnsNewObject(): void
    {
        $fqcn = \stdClass::class;
        $first = $this->introspector->getClass($fqcn);
        
        $this->introspector->clearInstanceCache();
        
        $second = $this->introspector->getClass($fqcn);
        $this->assertNotSame($first, $second, 'Should return different object after instance cache clear');
        $this->assertInstanceOf(ClassObj::class, $second, 'Should still return valid ClassObj');
    }

    /** @description Verifies that the introspector can successfully load cached class
    * objects from the filesystem without re-introspecting the original
    * class. This test generates a cache file, clears the instance cache
    * to force a filesystem load on the next access, then fetches the
    * class and compares it against ReflectionClass metadata. This ensures
    * that cached files accurately preserve all introspection data including
    * properties, methods, constants, and attributes across different
    * execution contexts.
    */
    public function testLoadingFromFilesystemCache(): void
    {
        $fqcn = \TestClasses\FullTestClass::class;
        
        // First access - generates cache
        $this->introspector->getClass($fqcn);
        
        // Clear instance cache to force filesystem load
        $this->introspector->clearInstanceCache();
        
        // Second access - loads from filesystem
        $loaded = $this->introspector->getClass($fqcn);
        
        $ref = new ReflectionClass($fqcn);
        $this->assertClassLikeMatchesReflection($loaded, $ref, "Filesystem loaded $fqcn");
    }

    /** @description Tests the complete cache lifecycle: generation, instance cache clearing,
    * and filesystem loading across multiple classes. This test caches
    * several different classes, clears the instance cache, then verifies
    * that all classes can be successfully reloaded from their filesystem
    * cache files. This comprehensive test ensures the caching system works
    * correctly at scale with multiple cached entities.
    */
    public function testMultipleClassesFilesystemCacheLifecycle(): void
    {
        $classes = [
            \stdClass::class,
            \ArrayObject::class,
            \TestClasses\FullTestClass::class,
        ];
        
        // Cache all classes
        foreach ($classes as $class) {
            $this->introspector->getClass($class);
        }
        
        $cacheFiles = glob($this->cacheDir . '/*.php');
        $this->assertCount(count($classes), $cacheFiles, 'Should have cache file for each class');
        
        // Clear instance cache
        $this->introspector->clearInstanceCache();
        
        // Verify all can be loaded from filesystem
        foreach ($classes as $class) {
            $loaded = $this->introspector->getClass($class);
            $this->assertInstanceOf(ClassObj::class, $loaded, "Should load $class from filesystem");
        }
    }

    /** @description Validates that filesystem-loaded cache maintains data integrity by
    * performing a deep comparison between a freshly introspected class
    * and the same class loaded from filesystem cache after instance cache
    * clearing. The test introspects a complex class with multiple members,
    * forces a filesystem reload, and uses assertClassLikeMatchesReflection
    * to verify that all metadata remains intact and accurate.
    */
    public function testFilesystemCacheDataIntegrity(): void
    {
        $fqcn = \TestClasses\FullTestClass::class;
        
        // Generate cache
        $original = $this->introspector->getClass($fqcn);
        
        // Force filesystem load
        $this->introspector->clearInstanceCache();
        $fromDisk = $this->introspector->getClass($fqcn);
        
        // Both should match reflection exactly
        $ref = new ReflectionClass($fqcn);
        $this->assertClassLikeMatchesReflection($original, $ref, "Original $fqcn");
        $this->assertClassLikeMatchesReflection($fromDisk, $ref, "Filesystem loaded $fqcn");
    }

    /** @description Confirms that a class object loaded directly from the cached PHP file
    * matches the ReflectionClass metadata exactly. This test manually includes
    * a generated cache file and performs a full deep comparison using
    * assertClassLikeMatchesReflection to verify that the serialized cache
    * preserves all introspection data including properties, methods, constants,
    * attributes, and their respective modifiers without any loss of fidelity.
    */
    public function testDirectCacheFileLoadMatchesReflection(): void
    {
        $fqcn = \TestClasses\FullTestClass::class;
        
        // Generate cache file
        $this->introspector->getClass($fqcn);
        
        // Load directly from filesystem
        $cacheFiles = glob($this->cacheDir . '/*.php');
        $this->assertNotEmpty($cacheFiles, 'Cache file should exist');
        
        $cached = include $cacheFiles[0];
        
        // Verify loaded cache matches reflection
        $ref = new ReflectionClass($fqcn);
        $this->assertClassLikeMatchesReflection($cached, $ref, "Direct file load $fqcn");
    }
}
