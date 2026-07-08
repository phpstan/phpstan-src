<?php

namespace ArrayShapeListOptional;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param list{0: string, 1: int, 2?: string, 3?: string} $valid1
	 * @param non-empty-list{0?: string, 1?: int, 2?: string, 3?: string} $valid2
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
		assertType('list{0: string, 1: int, 2?: string, 3?: string}', $valid1);
		assertType('list{0: string, 1?: int, 2?: string, 3?: string}', $valid2);
		assertType('non-empty-array{0?: string, 1?: int, 2?: string, 3?: string}', $valid3);
		// The trailing keys can never appear in a list (4 sits past the gap at
		// 3; foo is not an integer), so they are dropped, leaving the valid
		// list projection rather than an empty *NEVER*.
		assertType('array{0: string, 1: int, 2?: string}', $invalid1);
		assertType('array{0: string, 1: int, 2?: string}', $invalid2);
	}

}
