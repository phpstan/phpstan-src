<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14844Rule;

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
		return doFoo(
			fn () => array_map(fn (Someenum $type) => $type->value, Someenum::cases()),
		);
	}

}
