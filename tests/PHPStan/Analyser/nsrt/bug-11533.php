<?php declare(strict_types = 1);

namespace Bug11533;

use function PHPStan\Testing\assertType;

/** @param mixed[] $param */
function hello(array $param): void
{
	foreach (['need', 'field'] as $field) {
		if (!isset($param[$field]) || !is_string($param[$field])) {
			throw new \Exception();
		}
	}
	assertType("non-empty-array<mixed>&hasOffsetValue('field', string)&hasOffsetValue('need', string)", $param);
}

/** @param array{need: string, field: string} $param */
function world(array $param): void
{
}

/** @param mixed[] $param */
function helloWorld(array $param): void
{
	foreach (['need', 'field'] as $field) {
		if (!isset($param[$field]) || !is_string($param[$field])) {
			throw new \Exception();
		}
	}
	world($param);
}

/** @param mixed[] $param */
function withKey(array $param): void
{
	foreach (['need', 'field'] as $key => $field) {
		if (!isset($param[$field]) || !is_string($param[$field])) {
			throw new \Exception();
		}
	}
	assertType("non-empty-array<mixed>&hasOffsetValue('field', string)&hasOffsetValue('need', string)", $param);
}
