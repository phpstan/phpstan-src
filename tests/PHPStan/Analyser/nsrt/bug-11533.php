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

/** @param array<string, mixed> $data */
function helloWithArrayKeyExists(array $data): void
{
	foreach (['name', 'email'] as $key) {
		if (!array_key_exists($key, $data) || !is_string($data[$key])) {
			throw new \Exception();
		}
	}
	assertType("non-empty-array<string, mixed>&hasOffsetValue('email', string)&hasOffsetValue('name', string)", $data);
}
