<?php declare(strict_types = 1);

namespace Bug12665;

use function PHPStan\Testing\assertType;

class Broken
{
	/** @return array{a: string, b: int, c: int} */
	public function break(string $s, int $i): array
	{
		$array = ['a' => $s];
		foreach (['b', 'c'] as $letter) {
			$array[$letter] = $i;
		}
		assertType('array{a: string, b: int, c: int}', $array);
		return $array;
	}
}

class Broken2
{
	/**
	 * @param list<'b'|'c'> $letters
	 * @return array{a: string, b: int, c: int}
	 */
	public function break(string $s, int $i, array $letters): array
	{
		$array = ['a' => $s];
		foreach ($letters as $letter) {
			$array[$letter] = $i;
		}
		assertType('array{a: string, b?: int, c?: int}', $array);
		return $array;
	}
}
