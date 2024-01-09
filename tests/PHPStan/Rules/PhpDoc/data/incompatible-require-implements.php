<?php // lint >= 8.1

namespace IncompatibleRequireImplements;

/**
 * @phpstan-require-implements SomeTrait
 */
trait InvalidTrait1 {}

/**
 * @phpstan-require-implements SomeEnum
 */
trait InvalidTrait2 {}

/**
 * @phpstan-require-implements TypeDoesNotExist
 */
trait InvalidTrait3 {}

/**
 * @template T
 * @phpstan-require-implements SomeClass<T>
 */
trait InvalidTrait4 {}

/**
 * @phpstan-require-implements int
 */
trait InvalidTrait5 {}

/**
 * @phpstan-require-implements self&\stdClass
 */
trait InvalidTrait6 {}


/**
 * @phpstan-require-implements SomeClass
 */
class InvalidClass {}

/**
 * @phpstan-require-implements SomeClass
 */
enum InvalidEnum {}

class InValidTraitUse2
{
	use ValidTrait;
}

enum InvalidEnumTraitUse {
	use ValidTrait;
}

class InValidTraitUse extends SomeOtherClass implements WrongInterface
{
	use ValidTrait;
}

class ValidTraitUse extends SomeClass implements RequiredInterface
{
	use ValidTrait;
}

class ValidTraitUse2 extends ValidTraitUse
{
}

class ValidTraitUse3 extends ValidTraitUse
{
	use ValidTrait;
}

/**
 * @phpstan-require-implements RequiredInterface
 */
trait ValidTrait {}

interface WrongInterface
{

}

interface RequiredInterface
{

}

interface SomeInterface
{

}

trait SomeTrait
{

}

class SomeClass {}

class SomeSubClass extends SomeClass
{

}

class SomeOtherClass
{

}

enum SomeEnum
{

}

new class {
	use ValidTrait;
};

new class implements RequiredInterface {
	use ValidTrait;
};

class InvalidTraitUse1 {
	use InvalidTrait1;
}

class InvalidTraitUse2 {
	use InvalidTrait2;
}

class InvalidTraitUse3 {
	use InvalidTrait3;
}

class InvalidTraitUse4 {
	use InvalidTrait4;
}

class InvalidTraitUse5 {
	use InvalidTrait5;
}

class InvalidTraitUse6 {
	use InvalidTrait6;
}
