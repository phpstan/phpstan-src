<?php // lint >= 8.1

namespace Bug4890Php8;

use function PHPStan\Testing\assertType;

enum MyEnum
{
	case CASE1;
	case CASE2;

	public function someMethod(): bool { return true; }
}

class HelloWorld
{
	public function withEnumCase(\UnitEnum $entity): void
	{
		assertType('class-string<UnitEnum>', get_class($entity));
		assert(method_exists($entity, 'someMethod'));
		assertType('class-string<UnitEnum&hasMethod(someMethod)>', get_class($entity));
	}
}
