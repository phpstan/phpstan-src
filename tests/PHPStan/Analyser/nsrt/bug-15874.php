<?php declare(strict_types = 1);

namespace Bug15874;

use function PHPStan\Testing\assertType;

class Rel { public function with(): static { return $this; } }

/**
 * @param list<string> $keys
 * @param array<string, string> $fields
 */
function errorInClosureBody(array $keys, array $fields): void {
	$r = array_reduce($keys, function ($fields, $key) {
		assertType('mixed', $fields);
		$field = $fields[$key];
		assert($field instanceof Rel);
		$fields[$key] = $field->with();
		return $fields;
	}, $fields);
	assertType('mixed', $r);
}

function mixedArray(mixed $all): void
{
	$r = array_reduce($all, function ($acc, $item) {
		assertType('mixed', $acc);
		$acc[] = $item;
		return $acc;
	}, []);
	assertType('mixed', $r);
}

/** @param non-empty-list<\stdClass> $rows */
function nonEmptyWithoutInitial(array $rows): void
{
	$byOrder = array_reduce($rows, function ($collection, $row) {
		$collection[$row->order_id][] = $row;
		return $collection;
	});
	assertType('non-empty-array<non-empty-list<stdClass>>', $byOrder);
}

/** @param list<\stdClass> $rows */
function maybeEmptyWithoutInitial(array $rows): void
{
	$byOrder = array_reduce($rows, function ($collection, $row) {
		$collection[$row->order_id][] = $row;
		return $collection;
	});
	assertType('non-empty-array<non-empty-list<stdClass>>|null', $byOrder);
}
