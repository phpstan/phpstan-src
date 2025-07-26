<?php

namespace Levels\ArrayOffsetAccess;

class Foo {
	/** @return void */
	public function test(
		array $a,
		int|null $intOrNull,
		object|int $objectOrInt,
		object|null $objectOrNull,
		mixed $explicitlyMixed,
		$implicitlyMixed,
	) {
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
