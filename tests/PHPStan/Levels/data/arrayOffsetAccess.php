<?php

namespace Levels\ArrayOffsetAccess;

class Foo {
	/**
	 * @param array|\ArrayAccess $arrayOrObject
	 * @param int|null $intOrNull
	 * @param object|int $objectOrInt
	 * @param object|null $objectOrNull
	 * @param mixed $explicitlyMixed
	 */
	public function test(array $a, $arrayOrObject, $intOrNull, $objectOrInt, $objectOrNull, $explicitlyMixed, $implicitlyMixed): void
	{
		$a[42];
		$a[null];
		$a[new \DateTimeImmutable()];
		$a[$intOrNull];
		$a[$objectOrInt];
		$a[$objectOrNull];
		$a[$explicitlyMixed];
		$a[$implicitlyMixed];

		$arrayOrObject[42];
		$arrayOrObject[null];
		$arrayOrObject[new \DateTimeImmutable()];
		$arrayOrObject[$intOrNull];
		$arrayOrObject[$objectOrInt];
		$arrayOrObject[$objectOrNull];
		$arrayOrObject[$explicitlyMixed];
		$arrayOrObject[$implicitlyMixed];

		$explicitlyMixed[42];
		$explicitlyMixed[null];
		$explicitlyMixed[new \DateTimeImmutable()];
		$explicitlyMixed[$intOrNull];
		$explicitlyMixed[$objectOrInt];
		$explicitlyMixed[$objectOrNull];
		$explicitlyMixed[$explicitlyMixed];
		$explicitlyMixed[$implicitlyMixed];

		$implicitlyMixed[42];
		$implicitlyMixed[null];
		$implicitlyMixed[new \DateTimeImmutable()];
		$implicitlyMixed[$intOrNull];
		$implicitlyMixed[$objectOrInt];
		$implicitlyMixed[$objectOrNull];
		$implicitlyMixed[$explicitlyMixed];
		$implicitlyMixed[$implicitlyMixed];
	}
}
