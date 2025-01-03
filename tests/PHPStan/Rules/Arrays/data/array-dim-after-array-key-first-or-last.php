<?php // lint >= 8.0

declare(strict_types = 1);

namespace ArrayDimAfterArrayKeyFirstOrLast;

class HelloWorld
{
	/**
	 * @param list<string> $hellos
	 */
	public function last(array $hellos): string
	{
		if ($hellos !== []) {
			$last = array_key_last($hellos);
			return $hellos[$last];
		} else {
			$last = array_key_last($hellos);
			return $hellos[$last];
		}
	}

	/**
	 * @param array<string> $hellos
	 */
	public function lastOnArray(array $hellos): string
	{
		if ($hellos !== []) {
			$last = array_key_last($hellos);
			return $hellos[$last];
		}

		return 'nothing';
	}

	/**
	 * @param list<string> $hellos
	 */
	public function first(array $hellos): string
	{
		if ($hellos !== []) {
			$first = array_key_first($hellos);
			return $hellos[$first];
		}

		return 'nothing';
	}

	/**
	 * @param array<string> $hellos
	 */
	public function firstOnArray(array $hellos): string
	{
		if ($hellos !== []) {
			$first = array_key_first($hellos);
			return $hellos[$first];
		}

		return 'nothing';
	}

	/**
	 * @param array{first: int, middle: float, last: bool} $hellos
	 */
	public function shape(array $hellos): int|bool
	{
		$first = array_key_first($hellos);
		$last = array_key_last($hellos);

		if (rand(0,1)) {
			return $hellos[$first];
		}
		return $hellos[$last];
	}
}
