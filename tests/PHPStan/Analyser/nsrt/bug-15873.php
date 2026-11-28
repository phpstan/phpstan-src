<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15873Types;

use function PHPStan\Testing\assertType;

/** @param list<mixed>|array<string, mixed> $p */
function foreachByRef(array $p): void
{
	foreach ($p as &$v) {
	}
	assertType('array{}|non-empty-array<string, mixed>|non-empty-list<mixed>', $p);
}

/**
 * @param array<string, int|list<int>|array<string, int>> $data
 */
function offsetWrites(array $data): void
{
	$data['map'] = [];
	assertType("non-empty-array<string, array<string, int>|int|list<int>>&hasOffsetValue('map', array{})", $data);
	$data['map']['stars'] = 3;
	assertType("non-empty-array<string, array<string, int>|int|list<int>>&hasOffsetValue('map', array{stars: 3})", $data);
}

/**
 * @param list<string>|list<int> $list
 */
function join(array $list, bool|int|float|string $scalar, bool $c): void
{
	assertType('array{bool|float|int|string}|list<int>|list<string>', $c ? $list : [$scalar]);
	assertType("array{1, 'a'}|list<int>|list<string>", $c ? $list : [1, 'a']);
}

/** @param list<array{key: string}>|array<\stdClass> $m */
function narrowOffsetOfUnion(array $m, int|string $key): void
{
	if (is_array($m[$key])) {
		assertType('list<array{key: string}>', $m);
		$m[$key]['flag'] = true;
		assertType('non-empty-list<array{key: string, flag: true}>', $m);
	}
}

/** @param list<array{key: string}> $m */
function nestedWriteToTrackedOffset(array $m, int|string $key): void
{
	if (is_array($m[$key])) {
		$m[$key]['flag'] = true;
		assertType('non-empty-list<array{key: string, flag: true}>', $m);
	}
}

/** @param array<int, string>|array<string, int> $types */
function foreachTiesKeyValueAndIteratee(array $types): void
{
	foreach ($types as $key => $type) {
		assertType('int|string', $type);
		if (is_int($key)) {
			assertType('string', $type);
			assertType('non-empty-array<int, string>', $types);
		} else {
			assertType('int', $type);
			assertType('non-empty-array<string, int>', $types);
		}
	}
}

/** @param array<string, string> $values */
function writeToTrackedOffsetOfEmptyArrayUnderStringKey(array $values): void
{
	$data = [];
	foreach ($values as $name => $value) {
		$key = (string) $name;
		if (strlen($value) > 1) {
			if (!is_array($data[$key])) {
				$data[$key] = [];
			}
			$data[$key][] = $value;
		} else {
			$data[$key] = $value === '' ? null : $value;
		}
	}
	assertType('array<string, non-empty-array<mixed>|non-empty-string|null>', $data);
}
