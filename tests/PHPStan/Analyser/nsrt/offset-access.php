<?php

namespace OffsetAccess;

use function PHPStan\Testing\assertType;

/**
 * @template T of array<int, mixed>
 * @template K of key-of<T>
 * @param T $array
 * @param K $offset
 * @return T[K]
 */
function takeOffset(array $array, int $offset): mixed
{
	return $array[$offset];
}

function () {
	assertType('2', takeOffset([1, 2], 1));
};

/**
 * @template T of array<int, int>
 * @param T $array
 * @return T[($maybeZero is 0 ? 0 : key-of<T>)]
 */
function takeConditionalOffset(array $array, int $maybeZero): int
{
	return $array[0];
}

function () {
	assertType('1', takeConditionalOffset([1, 2, 3], 0));
	assertType('1|2|3', takeConditionalOffset([1, 2, 3], 1));
	assertType('1|2|3', takeConditionalOffset([1, 2, 3], 2));
};

/**
 * @return int[mixed]
 */
function impossibleOffset(int $value): mixed {
	return $value;
}
