<?php

declare(strict_types = 1);

namespace Bug14081;

class HelloWorld
{
	/**
	 * @param list<string> $list
	 */
	public function firstWithNullCheck(array $list): string
	{
		$key = array_key_first($list);
		if ($key !== null) {
			return $list[$key];
		}

		return 'nothing';
	}

	/**
	 * @param list<string> $list
	 */
	public function lastWithNullCheck(array $list): string
	{
		$key = array_key_last($list);
		if ($key !== null) {
			return $list[$key];
		}

		return 'nothing';
	}

	/**
	 * @param array<string, int> $map
	 */
	public function firstOnMapWithNullCheck(array $map): int
	{
		$key = array_key_first($map);
		if ($key !== null) {
			return $map[$key];
		}

		return 0;
	}

	/**
	 * @param array<string, int> $map
	 */
	public function lastOnMapWithNullCheck(array $map): int
	{
		$key = array_key_last($map);
		if ($key !== null) {
			return $map[$key];
		}

		return 0;
	}

	/**
	 * @param list<string> $list
	 */
	public function nullCheckReversed(array $list): string
	{
		$key = array_key_first($list);
		if (null !== $key) {
			return $list[$key];
		}

		return 'nothing';
	}
}
