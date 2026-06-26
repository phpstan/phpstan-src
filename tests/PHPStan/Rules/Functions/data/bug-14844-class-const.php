<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14844ClassConst;

final class Someclass
{

	public const FOO = 'foo';

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
		// Same sealed-sentinel bug as bug-14844, but the mapped value is a
		// class-constant fetch (`$type::FOO`) instead of an enum-case fetch.
		return doFoo(
			fn () => array_map(fn (Someclass $type) => $type::FOO, [new Someclass()]),
		);
	}

}
