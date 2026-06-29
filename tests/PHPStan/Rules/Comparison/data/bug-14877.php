<?php declare(strict_types = 1);

namespace Bug14877Rule;

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

		if (array_search($full, $a, true) !== false) {
			echo 'full';
		}

		if (array_search($subset, $a, true) !== false) {
			echo 'subset';
		}

		if (array_search($partial, $a, true) !== false) {
			echo 'partial';
		}
	}

	/**
	 * A general (non-constant) array does not guarantee that any particular value
	 * is present, so a subset needle must not be reported as always-found - even
	 * when every finite needle value appears in the array's value type.
	 *
	 * @param 1|2 $needle
	 * @param array<int, 1|2> $maybeEmpty
	 * @param non-empty-array<int, 1|2> $nonEmptyMulti
	 * @param non-empty-array<int, 1> $nonEmptySingle
	 */
	public function generalArrays(int $needle, array $maybeEmpty, array $nonEmptyMulti, array $nonEmptySingle): void
	{
		if (array_search($needle, $maybeEmpty, true) !== false) {
			echo 'maybeEmpty';
		}

		if (array_search($needle, $nonEmptyMulti, true) !== false) {
			echo 'nonEmptyMulti';
		}

		if (array_search(1, $nonEmptySingle, true) !== false) {
			echo 'nonEmptySingle';
		}
	}

}
