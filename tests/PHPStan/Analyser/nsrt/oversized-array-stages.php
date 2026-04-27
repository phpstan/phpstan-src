<?php

namespace OversizedArrayStages;

use function PHPStan\Testing\assertType;

/**
 * Phase 1: small enough that no generalization is needed. The
 * cumulative `countConstantArrayValueTypes` stays under
 * `ARRAY_COUNT_LIMIT`, so `optimizeConstantArrays` short-circuits and
 * each variant is preserved literally.
 */
function phase1Small(): array
{
	$arr = [];
	$arr[] = ['kind' => 'a', 'value' => 1];
	$arr[] = ['kind' => 'b', 'value' => 2];
	$arr[] = ['kind' => 'c', 'value' => 3];
	assertType("array{array{kind: 'a', value: 1}, array{kind: 'b', value: 2}, array{kind: 'c', value: 3}}", $arr);

	return $arr;
}

/**
 * Phase 2: conditional `$items[] = …` pushes leave behind a triangular
 * union of list variants of progressively longer length. Together
 * they push `countConstantArrayValueTypes` past `ARRAY_COUNT_LIMIT`,
 * which triggers the `reduceArrays` final-pass list-collapse: the
 * variants fold into `non-empty-list<unionValueType>` — the
 * `unionValueType` is the union of each variant's iterable value
 * type, which preserves the per-record `(kind, value, opts)`
 * correlation as a tagged union of the eight original record shapes.
 * Without the list-collapse, `optimizeConstantArrays`'s fallback
 * generalization would decompose every record into a flat
 * `non-empty-array<keyUnion, valueUnion>&oversized-array`, losing
 * both the per-record correlation and the sealed shape.
 */
function phase2TriangularCollapse(): array
{
	$items = [];

	if (rand()) {
		$items[] = ['kind' => 'k1', 'value' => 1, 'opts' => ['a' => 1]];
	}
	if (rand()) {
		$items[] = ['kind' => 'k2', 'value' => 2, 'opts' => ['a' => 2]];
	}
	if (rand()) {
		$items[] = ['kind' => 'k3', 'value' => 3, 'opts' => ['a' => 3]];
	}
	if (rand()) {
		$items[] = ['kind' => 'k4', 'value' => 4, 'opts' => ['a' => 4]];
	}
	if (rand()) {
		$items[] = ['kind' => 'k5', 'value' => 5, 'opts' => ['a' => 5]];
	}
	if (rand()) {
		$items[] = ['kind' => 'k6', 'value' => 6, 'opts' => ['a' => 6]];
	}
	if (rand()) {
		$items[] = ['kind' => 'k7', 'value' => 7, 'opts' => ['a' => 7]];
	}
	if (rand()) {
		$items[] = ['kind' => 'k8', 'value' => 8, 'opts' => ['a' => 8]];
	}

	if ($items === []) {
		return [];
	}

	assertType("non-empty-list<array{kind: 'k1', value: 1, opts: array{a: 1}}|array{kind: 'k2', value: 2, opts: array{a: 2}}|array{kind: 'k3', value: 3, opts: array{a: 3}}|array{kind: 'k4', value: 4, opts: array{a: 4}}|array{kind: 'k5', value: 5, opts: array{a: 5}}|array{kind: 'k6', value: 6, opts: array{a: 6}}|array{kind: 'k7', value: 7, opts: array{a: 7}}|array{kind: 'k8', value: 8, opts: array{a: 8}}>", $items);

	return $items;
}
