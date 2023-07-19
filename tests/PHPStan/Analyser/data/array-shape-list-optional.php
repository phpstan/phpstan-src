<?php

namespace ArrayShapeListOptional;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param list{0: string, 1: int, 2?: string, 3?: string} $valid1
	 * @param list{0: string, 1: int, 2?: string, 4?: string} $invalid1
	 * @param list{0: string, 1: int, 2?: string, foo?: string} $invalid2
	 */
	public function doFoo(
		$valid1,
		$invalid1,
		$invalid2
	): void
	{
		assertType('array{0: string, 1: int, 2?: string, 3?: string}&list', $valid1);
		assertType('*NEVER*', $invalid1);
		assertType('*NEVER*', $invalid2);
	}

}
