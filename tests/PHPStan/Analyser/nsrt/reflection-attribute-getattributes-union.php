<?php // lint >= 8.0

declare(strict_types=1);

namespace ReflectionAttributeGetAttributesUnion;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use function PHPStan\Testing\assertType;

#[\Attribute]
class MyAttr {}

// Single type — worked before
function testSingle(ReflectionClass $ref): void
{
	/** @var class-string<MyAttr> $class */
	$class = MyAttr::class;
	$attrs = $ref->getAttributes($class, ReflectionAttribute::IS_INSTANCEOF);
	assertType('list<ReflectionAttribute<ReflectionAttributeGetAttributesUnion\MyAttr>>', $attrs);
	assertType('ReflectionAttributeGetAttributesUnion\MyAttr', $attrs[0]->newInstance());
}

// Union type — dynamic extension should fire for all members
function testUnion(ReflectionClass|ReflectionProperty $ref): void
{
	/** @var class-string<MyAttr> $class */
	$class = MyAttr::class;
	$attrs = $ref->getAttributes($class, ReflectionAttribute::IS_INSTANCEOF);
	assertType('list<ReflectionAttribute<ReflectionAttributeGetAttributesUnion\MyAttr>>', $attrs);
	assertType('ReflectionAttributeGetAttributesUnion\MyAttr', $attrs[0]->newInstance());
}

// Template T flows through union
/**
 * @template T of object
 * @param ReflectionClass<object>|ReflectionProperty $reflection
 * @param class-string<T> $attributeClassName
 * @return T
 */
function getSingleAttribute(
	ReflectionClass|ReflectionProperty $reflection,
	string $attributeClassName,
): object
{
	$attributes = $reflection->getAttributes($attributeClassName, ReflectionAttribute::IS_INSTANCEOF);
	assertType('list<ReflectionAttribute<T of object (function ReflectionAttributeGetAttributesUnion\getSingleAttribute(), argument)>>', $attributes);

	$instance = $attributes[0]->newInstance();
	assertType('T of object (function ReflectionAttributeGetAttributesUnion\getSingleAttribute(), argument)', $instance);

	return $instance;
}
