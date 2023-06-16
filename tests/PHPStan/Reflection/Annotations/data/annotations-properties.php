<?php

namespace AnnotationsProperties;

use AllowDynamicProperties;
use OtherNamespace\Test as OtherTest;
use OtherNamespace\Ipsum;

/**
 * @property OtherTest $otherTest
 * @property-read Ipsum $otherTestReadOnly
 * @property self|Bar $fooOrBar
 * @property Ipsum $conflictingProperty
 * @property Foo $overriddenProperty
 */
#[AllowDynamicProperties]
class Foo implements FooInterface
{

	/** @var Foo */
	public $overriddenPropertyWithAnnotation;

}

/**
 * @property Bar $overriddenPropertyWithAnnotation
 * @property Foo $conflictingAnnotationProperty
 */
class Bar extends Foo
{

	/** @var Bar */
	public $overriddenProperty;

	/** @var Bar */
	public $conflictingAnnotationProperty;

}

/**
 * @property   Lorem  $bazProperty
 * @property Dolor $conflictingProperty
 * @property-write ?Lorem $writeOnlyProperty
 */
class Baz extends Bar
{

	use FooTrait;

}

/**
 * @property int | float $numericBazBazProperty
 */
class BazBaz extends Baz
{

}

/**
 * @property-read int $asymmetricPropertyRw
 * @property-write int|string $asymmetricPropertyRw
 *
 * @property int $asymmetricPropertyXw
 * @property-write int|string $asymmetricPropertyXw
 *
 * @property-read int $asymmetricPropertyRx
 * @property int|string $asymmetricPropertyRx
 */
#[AllowDynamicProperties]
class Asymmetric
{

}

/**
 * @property FooInterface $interfaceProperty
 */
interface FooInterface
{

}

/**
 * @property BazBaz $traitProperty
 */
trait FooTrait
{

}
