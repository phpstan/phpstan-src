<?php

namespace Bug15700Functions;

/**
 * @template T
 * @param array<T> $array
 * @return list<T>
 */
function userArrayValues(array $array): array
{
	return array_values($array);
}

/**
 * @param mixed $value
 */
function doFoo($value, $implicitMixed): void
{
	array_values($value);
	array_values($implicitMixed);
	userArrayValues($value);
}
