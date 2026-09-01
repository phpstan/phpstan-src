<?php declare(strict_types = 1);

namespace MinimalReWalk;

use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class Collection
{

	/** @param array<T> $items */
	public function __construct(array $items = [])
	{
	}

	/** @param T $item */
	public function add($item): void
	{
	}

}

class Foo
{

	/** @var Collection<int> */
	private Collection $ints;

	public function doFoo(int $x): void
	{
		$c = new Collection([1]);
		$this->ints = $c;
		assertType('MinimalReWalk\Collection<int>', $c);
		$a = $x + 1;
		$b = $a * 2;
		$d = $b - 1;
		$e = $d + $a;
		$f = [$a, $b];
		$g = $f[0] + $e;
		$h = $g > 3 ? $g : 3;
		$i = $h + 1;
		$j = $i * $x;
		assertType('int', $j);
	}

}
