<?php

namespace CallToMethodWithoutImpurePointsThrows;

class InvalidValue extends \Exception
{

}

final class Foo
{

	/**
	 * @param array<int> $ints
	 * @throws InvalidValue
	 */
	public function throwingMethod(array $ints)
	{
		foreach ($ints as $int) {
			if (!is_int($int)) {
				throw new InvalidValue();
			}
		}
	}

	public function noThrowsMethod()
	{
	}

}

function (Foo $foo): void {
	$foo->throwingMethod([1, 2, 3]);
	$foo->noThrowsMethod();
};
