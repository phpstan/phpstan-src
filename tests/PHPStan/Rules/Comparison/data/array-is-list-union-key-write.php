<?php

namespace ArrayIsListUnionKeyWrite;

class Foo
{

	/**
	 * @param 0|1 $int
	 * @param 'a'|'b' $string
	 */
	public function doFoo(int $int, string $string): void
	{
		$a = [];
		$a[$int] = 'x';
		if (array_is_list($a)) { // [0 => 'x'] is a list
		}

		$b = [];
		$b[$string] = 'x';
		if (array_is_list($b)) { // never a list while it is non-empty
		}

		array_pop($b);
		if (array_is_list($b)) { // [] is a list
		}
	}

	/**
	 * @param non-empty-array{a?: string, b?: int} $nonEmpty
	 * @param array{a?: string, b?: int} $maybeEmpty
	 */
	public function doBar(array $nonEmpty, array $maybeEmpty): void
	{
		if (array_is_list($nonEmpty)) {
		}

		if ($maybeEmpty === []) {
			return;
		}

		unset($maybeEmpty['a'], $maybeEmpty['b']);
		$maybeEmpty[] = 'x';
		if (array_is_list($maybeEmpty)) { // [0 => 'x'] is a list
		}
	}

}
