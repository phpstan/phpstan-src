<?php
// lint >= 8.0

namespace UnusedVariableArrowReference;

function normalize(array $values): array
{
	array_walk_recursive($values, fn (&$value) => $value = trim($value));
	return $values;
}

function callback(): \Closure
{
	$value = 1;
	return fn (&$value) => $value = 2;
}

function terminatingCallback(): \Closure
{
	return fn (&$value) => throw new \RuntimeException($value = 'changed');
}
