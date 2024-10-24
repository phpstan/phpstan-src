<?php

namespace ArrayShapeListOptional;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param list{0: string, 1: int, 2?: string, 3?: string} $valid1
	 * @param non-empty-list{0: string, 1: int, 2?: string, 3?: string} $valid2
	 * @param non-empty-array{0?: string, 1?: int, 2?: string, 3?: string} $valid3
	 * @param list{0: string, 1: int, 2?: string, 4?: string} $invalid1
	 * @param list{0: string, 1: int, 2?: string, foo?: string} $invalid2
	 */
	public function doFoo(
		$valid1,
		$valid2,
		$valid3,
		$invalid1,
		$invalid2
	): void
	{
		assertType('array{0: string, 1: int, 2?: string, 3?: string}&list', $valid1);
		assertType('array{0: string, 1: int, 2?: string, 3?: string}&list', $valid2);
		assertType('array{0?: string, 1?: int, 2?: string, 3?: string}&non-empty-array', $valid3);
		assertType('*NEVER*', $invalid1);
		assertType('*NEVER*', $invalid2);
	}

}
