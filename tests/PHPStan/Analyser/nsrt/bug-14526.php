<?php

namespace Bug14526;

use function array_merge;
use function array_replace;
use function PHPStan\Testing\assertType;

/**
 * @param array{array{foo: int}, array<string, int>}|array{} $values
 */
function testMergeUnpackUnionWithEmpty(array $values): void
{
	$result = array_merge(...$values);
	assertType('array<string, int>', $result);
}

/**
 * @param array{non-empty-array<string, int>, array<string, int>}|array{} $values
 */
function testMergeUnpackUnionNonEmptyFirstWithEmpty(array $values): void
{
	$result = array_merge(...$values);
	assertType('array<string, int>', $result);
}

/**
 * @param array{non-empty-array<string, int>}|array{} $values
 */
function testMergeUnpackUnionSingleWithEmpty(array $values): void
{
	$result = array_merge(...$values);
	assertType('array<string, int>', $result);
}

function testMergeUnpackConstantUnionWithEmpty(): void
{
	$values = rand(0, 1) ? [['a' => 1], ['b' => 2]] : [];
	$result = array_merge(...$values);
	assertType('array{a?: 1, b?: 2}', $result);
}

function testMergeUnpackConstantUnionWithEmptyThreeElements(): void
{
	$values = rand(0, 1) ? [['a' => 1], ['b' => 2], ['c' => 3]] : [];
	$result = array_merge(...$values);
	assertType('array{a?: 1, b?: 2, c?: 3}', $result);
}

/**
 * @param array{array{foo: int}, array<string, int>}|array{} $values
 */
function testReplaceUnpackUnionWithEmpty(array $values): void
{
	$result = array_replace(...$values);
	assertType('array<string, int>', $result);
}

/**
 * @param array{non-empty-array<string, int>, array<string, int>}|array{} $values
 */
function testReplaceUnpackUnionNonEmptyFirstWithEmpty(array $values): void
{
	$result = array_replace(...$values);
	assertType('array<string, int>', $result);
}

function testReplaceUnpackConstantUnionWithEmpty(): void
{
	$values = rand(0, 1) ? [['a' => 1], ['b' => 2]] : [];
	$result = array_replace(...$values);
	assertType('array{a?: 1, b?: 2}', $result);
}
