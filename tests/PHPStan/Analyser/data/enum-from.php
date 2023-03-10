<?php // lint >= 8.1

namespace EnumFrom;

use function PHPStan\Testing\assertType;

enum FooIntegerEnum: int
{

	case BAR = 1;
	case BAZ = 2;

}

enum FooStringEnum: string
{

	case BAR = 'bar';
	case BAZ = 'baz';

}

class Foo
{

	public function doFoo(): void
	{
		assertType('1', FooIntegerEnum::BAR->value);
		assertType('EnumFrom\FooIntegerEnum::BAR', FooIntegerEnum::BAR);

		assertType('null', FooIntegerEnum::tryFrom(0));
		assertType(FooIntegerEnum::class, FooIntegerEnum::from(0));
		assertType('EnumFrom\FooIntegerEnum::BAR', FooIntegerEnum::tryFrom(0 + 1));
		assertType('EnumFrom\FooIntegerEnum::BAR', FooIntegerEnum::from(1 * FooIntegerEnum::BAR->value));

		assertType('EnumFrom\FooIntegerEnum::BAZ', FooIntegerEnum::tryFrom(2));
		assertType('EnumFrom\FooIntegerEnum::BAZ', FooIntegerEnum::tryFrom(FooIntegerEnum::BAZ->value));
		assertType('EnumFrom\FooIntegerEnum::BAZ', FooIntegerEnum::from(FooIntegerEnum::BAZ->value));

		assertType("'bar'", FooStringEnum::BAR->value);
		assertType('EnumFrom\FooStringEnum::BAR', FooStringEnum::BAR);

		assertType('null', FooStringEnum::tryFrom('barz'));
		assertType(FooStringEnum::class, FooStringEnum::from('barz'));

		assertType('EnumFrom\FooStringEnum::BAR', FooStringEnum::tryFrom('ba' . 'r'));
		assertType('EnumFrom\FooStringEnum::BAR', FooStringEnum::from(sprintf('%s%s', 'ba', 'r')));

		assertType('EnumFrom\FooStringEnum::BAZ', FooStringEnum::tryFrom('baz'));
		assertType('EnumFrom\FooStringEnum::BAZ', FooStringEnum::tryFrom(FooStringEnum::BAZ->value));
		assertType('EnumFrom\FooStringEnum::BAZ', FooStringEnum::from(FooStringEnum::BAZ->value));
	}

}
