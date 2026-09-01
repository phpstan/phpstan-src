<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug10419;

use DateTime;

/**
 * @template T
 */
class Foo {
	/**
	 * @param T $value
	 */
	public function __construct(public $value) {}
}

/**
 * @return Foo<array<string, array{
 *    boolKey: bool,
 *    naturalKey: non-negative-int,
 * }>>
 */
function fail(): Foo {
	return new Foo([
		(new DateTime)->format('Y-m-d') => [
			'boolKey' => false,
			'naturalKey' => 0,
		],
	]);
}
