<?php

namespace CallToStaticMethodWithoutImpurePointsThrows;

class InvalidValue extends \Exception
{

}

final class Foo
{

	/**
	 * @param array<int> $ints
	 * @throws InvalidValue
	 */
	public static function throwingStatic(array $ints)
	{
		foreach ($ints as $int) {
			if (!is_int($int)) {
				throw new InvalidValue();
			}
		}
	}

	public static function noThrowsStatic()
	{
	}

}

function (): void {
	Foo::throwingStatic([1, 2, 3]);
	Foo::noThrowsStatic();
};
