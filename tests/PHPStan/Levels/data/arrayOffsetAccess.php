<?php

namespace Levels\ArrayOffsetAccess;

class Foo {
	/**
	 * @param int|null $intOrNull
	 * @param object|int $objectOrInt
	 * @param object|null $objectOrNull
	 * @param mixed $explicitlyMixed
	 * @return void
	 */
	public function test(array $a, $intOrNull, $objectOrInt, $objectOrNull, $explicitlyMixed, $implicitlyMixed)
	{
		$a[42];
		$a[null];
		$a[new \DateTimeImmutable()];
		$a[$intOrNull];
		$a[$objectOrInt];
		$a[$objectOrNull];
		$a[$explicitlyMixed];
		$a[$implicitlyMixed];
	}
}
