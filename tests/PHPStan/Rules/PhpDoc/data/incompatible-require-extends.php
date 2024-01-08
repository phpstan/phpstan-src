<?php // lint >= 8.1

namespace IncompatibleRequireExtends;

/**
 * @phpstan-require-extends SomeTrait
 */
interface InvalidInterface1 {}

/**
 * @phpstan-require-extends SomeInterface
 */
interface InvalidInterface2 {}

/**
 * @phpstan-require-extends SomeEnum
 */
interface InvalidInterface3 {}

/**
 * @phpstan-require-extends TypeDoesNotExist
 */
interface InvalidInterface4 {}

/**
 * @template T
 * @phpstan-require-extends SomeClass<T>
 */
interface InvalidInterface5 {}

/**
 * @phpstan-require-extends int
 */
interface InvalidInterface6 {}

/**
 * @phpstan-require-extends SomeClass
 */
class InvalidClass {}

/**
 * @phpstan-require-extends SomeClass
 */
enum InvalidEnum {}

class InValidTraitUse2
{
	use ValidTrait;
}

class InValidTraitUse extends SomeOtherClass
{
	use ValidTrait;
}

class InvalidInterfaceUse2 implements ValidInterface {}

class InvalidInterfaceUse extends SomeOtherClass implements ValidInterface {}

class ValidInterfaceUse extends SomeClass implements ValidInterface {}

class ValidTraitUse extends SomeClass
{
	use ValidTrait;
}

class ValidTraitUse2 extends SomeSubClass
{
	use ValidTrait;
}
/**
 * @phpstan-require-extends SomeClass
 */
interface ValidInterface {}

/**
 * @phpstan-require-extends SomeClass
 */
trait ValidTrait {}



interface SomeInterface
{

}

trait SomeTrait
{

}

class SomeClass
{

}

final class SomeFinalClass
{

}

class SomeSubClass extends SomeClass
{

}

class SomeOtherClass
{

}

enum SomeEnum
{

}

/**
 * @phpstan-require-extends SomeFinalClass
 */
interface InvalidInterface7 {}

/**
 * @phpstan-require-extends SomeFinalClass
 */
trait InvalidTrait {}

class InvalidClass3 {
	use InvalidTrait;
}

/**
 * @phpstan-require-extends self&\stdClass
 */
interface UnresolvableExtendsInterface {}

/**
 * @phpstan-require-extends self&\stdClass
 */
trait UnresolvableExtendsTrait {}

class InvalidClass3 {
	use UnresolvableExtendsTrait;
}
