<?php // lint >= 8.0

namespace ArrayDimFetchOnArrayKeyFirstOrLast;

class Hello {
	/**
	 * @param list<string> $hellos
	 */
	public function first(array $hellos, array $anotherArray): string
	{
		if (rand(0,1)) {
			return $hellos[array_key_first($hellos)];
		}
		if ($hellos !== []) {
			if ($anotherArray !== []) {
				return $hellos[array_key_first($anotherArray)];
			}

			return $hellos[array_key_first($hellos)];
		}
		return '';
	}

	/**
	 * @param array<string> $hellos
	 */
	public function last(array $hellos): string
	{
		if ($hellos !== []) {
			return $hellos[array_key_last($hellos)];
		}
		return '';
	}

	/**
	 * @param list<string> $hellos
	 */
	public function countOnArray(array $hellos, array $anotherArray): string
	{
		if ($hellos === []) {
			return 'nothing';
		}

		if (rand(0,1)) {
			return $hellos[count($anotherArray) - 1];
		}

		return $hellos[count($hellos) - 1];
	}

	/**
	 * @param non-empty-array<string> $hellos
	 */
	public function namedRandom(array $hellos): string
	{
		return $hellos[array_rand(num: 1, array: $hellos)];
	}
}
