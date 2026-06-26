<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14844;

enum SomeEnum: string
{
	case FOO = 'foo';
}

class A
{

	/**
	 * @return array<string>
	 */
	public function doBar(): array
	{
		return doFoo(
			fn () => array_map(fn (SomeEnum $type) => $type->value, SomeEnum::cases()),
		);
	}

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
