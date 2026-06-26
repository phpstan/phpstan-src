<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14844;

use function PHPStan\Testing\assertType;

enum Someenum: string
{
	case FOO = 'foo';
}

/**
 * @template TReturn
 * @param callable(): TReturn $callable
 * @return TReturn
 */
function doFoo(callable $callable)
{
	return $callable();
}

class A
{

	/**
	 * @return array<string>
	 */
	public function doBar(): array
	{
		assertType("array{'foo'}", doFoo(
			fn () => array_map(fn (Someenum $type) => $type->value, Someenum::cases()),
		));

		return doFoo(
			fn () => array_map(fn (Someenum $type) => $type->value, Someenum::cases()),
		);
	}

}
