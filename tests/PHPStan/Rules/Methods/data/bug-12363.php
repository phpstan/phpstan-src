<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug12363Methods;

/**
 * @template Y of 'a'|'b'
 */
class A
{
	/**
	 * @param Y $y
	 */
	public function __construct(
		public readonly int $x,
		public readonly string $y = 'a',
	) {
	}
}

$a = new A(...['x' => 5, 'y' => 'b']);
