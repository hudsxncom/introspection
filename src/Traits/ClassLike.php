<?php

namespace Hudsxn\Introspection\Traits;

use Hudsxn\Introspection\Structures\Attribute;
use Hudsxn\Introspection\Structures\Constant;
use Hudsxn\Introspection\Structures\Property;
use Hudsxn\Introspection\Structures\Method;
use ReflectionClass;

/**
 * Trait ClassLike
 *
 * Provides common functionality for class-like structures:
 * classes, interfaces, and traits.
 *
 * Features:
 * - Type and namespace handling
 * - Properties, methods, and constants management
 * - Modifiers, inheritance, traits, and attributes integration
 * - PHP source generation for reconstruction or code generation
 *
 * @property string        $namespace  Namespace of the class-like structure
 * @property int           $type       One of TYPE_CLASS, TYPE_INTERFACE, TYPE_TRAIT
 * @property Property[]    $properties List of properties indexed by name
 * @property Method[]      $methods    List of methods indexed by name
 * @property Constant[]    $constants  List of constants indexed by name
 */
trait ClassLike
{
    use CommonFields;
    use Modifiers;
    use ExtendsSomething;
    use UseTraits;
    use Attributes;

    /** @var string The namespace of the class-like entity */
    private string $namespace;

    /** @var int One of TYPE_CLASS, TYPE_INTERFACE, TYPE_TRAIT */
    private int $type;

    /** @var Property[] Indexed by property name */
    private array $properties = [];

    /** @var Method[] Indexed by method name */
    private array $methods = [];

    /** @var Constant[] Indexed by constant name */
    private array $constants = [];

    // ------------------ Namespace & Type ------------------

    /**
     * Set the namespace of the class-like entity.
     *
     * @param string $namespace
     * @return static Fluent self reference
     */
    public function setNamespace(string $namespace): static
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * Get the namespace of the class-like entity.
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    private string $shortNameCache;

    public function getShortName(): string
    {
        if (isset($this->shortNameCache))
        {
            return $this->shortNameCache;
        }
        $this->shortNameCache = basename(str_replace('\\', '/', $this->getName()));
        return $this->shortNameCache;
    }

    /**
     * Set the type of the class-like entity.
     *
     * @param int $type One of TYPE_CLASS, TYPE_INTERFACE, TYPE_TRAIT
     * @return static Fluent self reference
     */
    public function setType(int $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get the type of the class-like entity.
     *
     * @return int One of TYPE_CLASS, TYPE_INTERFACE, TYPE_TRAIT
     */
    public function getType(): int
    {
        return $this->type;
    }

    // ------------------ Properties ------------------

    /**
     * Add a property to this class-like entity.
     *
     * @param Property $property
     * @return static Fluent self reference
     */
    public function addProperty(Property $property): static
    {
        $this->properties[$property->getName()] = $property;
        return $this;
    }

    /**
     * Check if a property exists by name.
     *
     * @param string $name
     * @return bool
     */
    public function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    /**
     * Get a property by name.
     *
     * @param string $name
     * @return Property|null
     */
    public function getProperty(string $name): ?Property
    {
        return $this->properties[$name] ?? null;
    }

    /**
     * Get all properties as a numeric array.
     *
     * @return Property[]
     */
    public function getProperties(): array
    {
        return array_values($this->properties);
    }

    // ------------------ Methods ------------------

    /**
     * Add a method to this class-like entity.
     *
     * @param Method $method
     * @return static Fluent self reference
     */
    public function addMethod(Method $method): static
    {
        $this->methods[$method->getName()] = $method;
        return $this;
    }

    /**
     * Check if a method exists by name.
     *
     * @param string $name
     * @return bool
     */
    public function hasMethod(string $name): bool
    {
        return isset($this->methods[$name]);
    }

    /**
     * Get a method by name.
     *
     * @param string $name
     * @return Method|null
     */
    public function getMethod(string $name): ?Method
    {
        return $this->methods[$name] ?? null;
    }

    /**
     * Get all methods as a numeric array.
     *
     * @return Method[]
     */
    public function getMethods(): array
    {
        return array_values($this->methods);
    }

    // ------------------ Constants ------------------

    /**
     * Check if a constant exists by name.
     *
     * @param string $name
     * @return bool
     */
    public function hasConstant(string $name): bool
    {
        return isset($this->constants[$name]);
    }

    /**
     * Add a constant to this class-like entity.
     *
     * @param Constant $constant
     * @return static Fluent self reference
     */
    public function addConstant(Constant $constant): static
    {
        $this->constants[$constant->getName()] = $constant;
        return $this;
    }

    /**
     * Get a constant by name.
     *
     * @param string $name
     * @return Constant|null
     */
    public function getConstant(string $name): ?Constant
    {
        return $this->constants[$name] ?? null;
    }

    /**
     * Get the number of constants.
     *
     * @return int
     */
    public function constantCount(): int
    {
        return \count($this->constants);
    }

    public function from(ReflectionClass $reflection): static
    {
        $this->setName($reflection->getName());
        $this->shortNameCache = $reflection->getShortName();
        $this->setNamespace($reflection->getNamespaceName());

        // ---------------- Methods ----------------
        foreach ($reflection->getMethods() as $method) {
            $this->addMethod((new Method())->from($method));
        }

        // ---------------- Constants ----------------
        foreach ($reflection->getReflectionConstants() as $const) {
            $this->addConstant((new Constant())->from($const));
        }

        // ---------------- Properties ----------------
        foreach ($reflection->getProperties() as $property) {
            $this->addProperty((new Property())->from($property));
        }

        if (!$reflection->isInterface() && !$reflection->isTrait())
        {
            // ---------------- Implements ----------------
            foreach ($reflection->getInterfaceNames() as $interface) {
                $this->addImplement($interface);
            }
        }

        // ---------------- Traits ----------------
        foreach ($reflection->getTraits() as $trait) {
            $this->addTrait($trait->getName());
        }

        // ---------------- Parent ----------------
        if ($parent = $reflection->getParentClass()) {
            $this->setExtends($parent->getName());
        }

        // ---------------- Attributes ----------------
        foreach ($reflection->getAttributes() as $attribute) {
            $this->addAttribute((new Attribute())->from($attribute));
        }

        // ---------------- Modifiers ----------------
        $this->setFinal($reflection->isFinal())
            ->setAbstract($reflection->isAbstract());

        return $this;
    }

    public function toSource(array &$lines)
    {
        // Start the object creation
        $lines[] = '(new ' . static::class . '()';

        // ------------------ Name ------------------
        $lines[] = '->setName(' . var_export($this->getName(), true) . ')';

        // ------------------ Namespace ------------------
        if (isset($this->namespace)) {
            $lines[] = '->setNamespace(' . var_export($this->namespace, true) . ')';
        }

        // ------------------ Type ------------------
        if (isset($this->type)) {
            $lines[] = '->setType(' . var_export($this->type, true) . ')';
        }

        // ------------------ Properties ------------------
        foreach ($this->properties as $property) {
            $property->toSource($lines);
        }

        // ------------------ Constants ------------------
        foreach ($this->constants as $constant) {
            $constant->toSource($lines);
        }

        // ------------------ Methods ------------------
        foreach ($this->methods as $method) {
            $method->toSource($lines);
        }

        // ------------------ Traits ------------------
        if (!empty($this->traits)) {
            foreach ($this->traits as $trait) {
                $lines[] = '->addTrait(' . var_export($trait, true) . ')';
            }
        }

        // ------------------ Implements / Interfaces ------------------
        if (!empty($this->implements)) {
            foreach ($this->implements as $interface) {
                $lines[] = '->addImplement(' . var_export($interface, true) . ')';
            }
        }

        // ------------------ Extends ------------------
        if (!empty($this->extends)) {
            $lines[] = '->setExtends(' . var_export($this->extends, true) . ')';
        }

        // ------------------ Attributes ------------------
        if (!empty($this->attributes)) {
            foreach ($this->attributes as $attribute) {
                $lines[] = '->addAttribute(';
                $attribute->toSource($lines);
                $lines[] = ')';
            }
        }

        // ------------------ Modifiers ------------------
        if ($this->isFinal()) {
            $lines[] = '->setFinal(true)';
        }
        if ($this->isAbstract()) {
            $lines[] = '->setAbstract(true)';
        }

        // Close the object creation
        $lines[] = ')';
    }

}
