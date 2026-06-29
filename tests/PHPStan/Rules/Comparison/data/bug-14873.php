<?php declare(strict_types = 1);

namespace Bug14873Rule;

class HelloWorld
{

	/**
	 * @param 'a'|'b'|'c' $full
	 * @param 'a'|'b' $subset
	 * @param 'a'|'x' $partial
	 */
	public function sayHello(string $full, string $subset, string $partial): void
	{
		$a = ['a', 'b', 'c'];

		if (in_array($full, $a, true)) {
			echo 'full';
		}

		if (in_array($subset, $a, true)) {
			echo 'subset';
		}

		if (in_array($partial, $a, true)) {
			echo 'partial';
		}
	}

	/**
	 * A general (non-constant) array does not guarantee that any particular value
	 * is present, so a subset needle must not be reported as always-true - even
	 * when every finite needle value appears in the array's value type.
	 *
	 * @param 1|2 $needle
	 * @param array<int, 1|2> $maybeEmpty
	 * @param non-empty-array<int, 1|2> $nonEmptyMulti
	 */
	public function generalArrays(int $needle, array $maybeEmpty, array $nonEmptyMulti): void
	{
		if (in_array($needle, $maybeEmpty, true)) {
			echo 'maybeEmpty';
		}

		if (in_array($needle, $nonEmptyMulti, true)) {
			echo 'nonEmptyMulti';
		}
	}

}
