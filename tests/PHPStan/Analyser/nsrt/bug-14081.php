<?php

declare(strict_types = 1);

namespace Bug14081Nsrt;

use function PHPStan\Testing\assertType;

/** @param list<string> $list */
function firstWithNullCheck(array $list): void
{
	$key = array_key_first($list);
	if ($key !== null) {
		assertType('non-empty-list<string>', $list);
		assertType('int<0, max>', $key);
		assertType('string', $list[$key]);
	}
}

/** @param list<string> $list */
function lastWithNullCheck(array $list): void
{
	$key = array_key_last($list);
	if ($key !== null) {
		assertType('non-empty-list<string>', $list);
		assertType('int<0, max>', $key);
		assertType('string', $list[$key]);
	}
}

/** @param array<string, int> $map */
function firstOnMapWithNullCheck(array $map): void
{
	$key = array_key_first($map);
	if ($key !== null) {
		assertType('non-empty-array<string, int>', $map);
		assertType('string', $key);
		assertType('int', $map[$key]);
	}
}

/** @param iterable<string, int> $data */
function iterableWithNullCheck(iterable $data): void
{
	$key = array_key_first($data);
	if ($key !== null) {
		assertType('mixed', $data[$key]);
	}
}
