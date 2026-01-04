# Hudsxn Introspection

Hudsxn Introspection is a **high-performance PHP library** for full-depth class, interface, and trait introspection. It captures **properties, constants, methods, method arguments, and attributes** while supporting **memory caching**, **filesystem-backed caching**, and selective or full refresh modes. Ideal for frameworks, static analysis tools, IDE integrations, and any system that requires reliable reflection metadata.

---

## Features

* Deep introspection of **classes, interfaces, and traits**
* **Memory caching** for repeated accesses
* **Filesystem caching** to persist introspection results across requests
* **Refresh mode** to regenerate metadata when source changes
* **Selective refresh** to optimize performance in large systems
* Singleton-based introspector for centralized caching and consistent results
* Fully **tested** and production-ready

---

## Installation

Install via Composer:

```bash
composer require hudsxn/introspection
```

---

## Basic Usage

```php
use Hudsxn\Introspection\Introspector;

// Get singleton instance
$introspector = Introspector::getSelf();

// Initialize with cache directory
$introspector->init(__DIR__ . '/cache');

// Set mode (fastest in-memory caching)
$introspector->setMode(Introspector::MODE_FASTEST);

// Introspect a class
$classObj = $introspector->getClass(MyApp\ExampleClass::class);

// Access properties
foreach ($classObj->getProperties() as $property) {
    echo $property->getName() . ': ' . $property->getType() . PHP_EOL;
}

// Access methods and arguments
foreach ($classObj->getMethods() as $method) {
    echo $method->getName() . '(): ' . $method->getReturnType() . PHP_EOL;
    foreach ($method->getArguments() as $arg) {
        echo '  - ' . $arg->getName() . ': ' . $arg->getType() . PHP_EOL;
    }
}
```

---

## Refresh Modes

* **Default:** Uses memory and filesystem caches
* **Refresh:** Regenerates class metadata from source
* **Selective Refresh:** Only refresh specific classes

```php
// Refresh a single class
$introspector->setMode([\MyApp\ExampleClass::class]);
$refreshedClass = $introspector->getClass(MyApp\ExampleClass::class);
```

---

## Filesystem Cache

* The library can **persist introspected metadata to disk** for faster repeated access.
* Clearing memory cache does **not remove filesystem cache**, so subsequent requests will load cached PHP files.

```php
// Clear only memory cache
$introspector->clearInstanceCache();

// Clear memory + filesystem cache
$introspector->clearCache();
```

---

## Performance Benefits

| Operation                             | Without Cache         | With Memory Cache | With Filesystem Cache           |
| ------------------------------------- | --------------------- | ----------------- | ------------------------------- |
| First introspection                   | 100% parsing overhead | N/A               | N/A                             |
| Second introspection                  | 100% parsing          | Near-instant      | Near-instant                    |
| Subsequent request after memory clear | 100% parsing          | N/A               | Near-instant (loaded from disk) |

* **Memory cache** ensures repeated accesses are **nearly instantaneous**.
* **Filesystem cache** avoids repeated reflection parsing across processes.
* Works seamlessly for **hundreds of classes** with negligible overhead.

---

## Example: Deep Comparison

```php
use Hudsxn\Introspection\Structures\ClassObj;
use ReflectionClass;

$introspector = Introspector::getSelf();
$introspector->init(__DIR__ . '/cache');

$classObj = $introspector->getClass(MyApp\ExampleClass::class);
$reflection = new ReflectionClass(MyApp\ExampleClass::class);

// Compare metadata (example)
if ($classObj instanceof ClassObj) {
    echo "Class {$classObj->getName()} successfully introspected!" . PHP_EOL;
}
```

---

## Testing

Run full test suite:

```bash
composer install
vendor/bin/phpunit
```

* The library is fully tested for **memory caching, filesystem caching, refresh modes**, and **deep introspection** of classes, interfaces, and traits.
* Tests validate that cached files maintain **100% fidelity** with native PHP reflection.

---

## Contributing

Contributions are welcome! Please submit pull requests or open issues on GitHub.

* Follow PSR-12 coding standards
* Include tests for new features or fixes
* Ensure **cache and introspection integrity**

---

## License

MIT License – see [LICENSE](LICENSE)

---