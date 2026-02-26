<?php

namespace Levels\ArraySum;

class Foo
{
	/**
	 * @param array<int> $arrayOfInt
	 * @param array<string> $arrayOfString
	 * @param array<int|string> $arrayOfUnion
	 * @param mixed $explicitlyMixed
	 */
	public function test(array $arrayOfInt, array $arrayOfString, array $arrayOfUnion, $explicitlyMixed, $implicitlyMixed): void
	{
		$objectOrArrayOfInt = rand(0, 1) ? new Foo() : $arrayOfInt;
		$objectOrArrayOfString = rand(0, 1) ? new Foo() : $arrayOfString;
		$objectOrArrayOfUnion = rand(0, 1) ? new Foo() : $arrayOfUnion;

		array_sum($arrayOfInt);
		array_sum($arrayOfString);
		array_sum($arrayOfUnion);
		array_sum($objectOrArrayOfInt);
		array_sum($objectOrArrayOfString);
		array_sum($objectOrArrayOfUnion);
		array_sum($explicitlyMixed);
		array_sum($implicitlyMixed);
	}
}
