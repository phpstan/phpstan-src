<?php

namespace ArrayDestructuringMaybeArray;

class Foo
{

	/**
	 * @param array{0?: int, 1: int}|false $value
	 */
	public function optionalKeyOrFalse($value): void
	{
		[$a, $b] = $value;
	}

	/**
	 * @param array{0: int, 1: int}|false $value
	 */
	public function requiredKeysOrFalse($value): void
	{
		[$a, $b] = $value;
	}

	/**
	 * @param array{0: array{0?: int}}|false $value
	 */
	public function nested($value): void
	{
		[[$a]] = $value;
	}

	/**
	 * @param \ArrayAccess<int, int>|false $value
	 */
	public function arrayAccessOrFalse($value): void
	{
		[$a] = $value;
	}

}
