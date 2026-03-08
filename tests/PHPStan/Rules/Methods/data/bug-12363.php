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

$aa = new A(...[5, 'y' => 'b']);

$aaa = new A(...[5, 'b']);

$aaaa = new A(...[1 => 5, 2 => 'b']);

/**
 * @template Y of 'a'|'b'
 */
class B
{
	/**
	 * @param Y $y
	 */
	public function __construct(
		public readonly int $init,
		public readonly int $x,
		public readonly string $y = 'a',
	) {
	}
}

$a = new B(1, ...['x' => 5, 'y' => 'b']);

$aa = new B(1, ...[5, 'y' => 'b']);

$aaa = new B(1, ...[5, 'b']);

$aaaa = new B(1, ...[1 => 5, 2 => 'b']);
