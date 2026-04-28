<?php

namespace Bug1940;

use function PHPStan\Testing\assertType;

/**
 * @param array<int, string> $arr
 */
function byRefWithoutKey(array $arr): void
{
	foreach ($arr as &$v) {
		$v = 1;
	}

	assertType('array<int, 1>', $arr);
}

/**
 * @param array<int, string> $arr
 */
function byRefWithoutKeyConditional(array $arr): void
{
	foreach ($arr as &$v) {
		if (rand(0, 1)) {
			$v = 1;
		}
	}

	assertType('array<int, 1|string>', $arr);
}

/**
 * @param array<int, string> $arr
 */
function byRefWithoutKeyAlwaysOverwritten(array $arr): void
{
	foreach ($arr as &$v) {
		if (rand(0, 1)) {
			$v = 1;
		} else {
			$v = 2;
		}
	}

	assertType('array<int, 1|2>', $arr);
}

/**
 * @param list<string> $arr
 */
function byRefWithoutKeyList(array $arr): void
{
	foreach ($arr as &$v) {
		$v = 'replaced';
	}

	assertType("list<'replaced'>", $arr);
}

/**
 * @param array<string, int> $arr
 */
function byRefWithoutKeyStringKeys(array $arr): void
{
	foreach ($arr as &$v) {
		$v = 'hello';
	}

	assertType("array<string, 'hello'>", $arr);
}

/**
 * @param non-empty-array<int, string> $arr
 */
function byRefWithoutKeyNonEmpty(array $arr): void
{
	foreach ($arr as &$v) {
		$v = 42;
	}

	assertType('non-empty-array<int, 42>', $arr);
}

/**
 * By-ref without key with break — should NOT rewrite since not all elements may be visited.
 * @param array<int, string> $arr
 */
function byRefWithoutKeyBreak(array $arr): void
{
	foreach ($arr as &$v) {
		$v = 1;
		if (rand(0, 1)) {
			break;
		}
	}

	assertType('array<int, 1|string>', $arr);
}

/**
 * By-ref without key with continue — should still rewrite since all elements are visited.
 * @param array<int, string> $arr
 */
function byRefWithoutKeyContinue(array $arr): void
{
	foreach ($arr as &$v) {
		$v = 1;
		if (rand(0, 1)) {
			continue;
		}
	}

	assertType('array<int, 1>', $arr);
}

/**
 * By-ref without key with continue where value is overwritten in all branches.
 * @param array<int, string> $arr
 */
function byRefWithoutKeyContinueBranches(array $arr): void
{
	foreach ($arr as &$v) {
		if (rand(0, 1)) {
			$v = 1;
			continue;
		}
		$v = 2;
	}

	assertType('array<int, 1|2>', $arr);
}

/**
 * By-ref without key with continue where value is NOT overwritten in the continue branch.
 * @param array<int, string> $arr
 */
function byRefWithoutKeyContinuePartial(array $arr): void
{
	foreach ($arr as &$v) {
		if (rand(0, 1)) {
			continue;
		}
		$v = 1;
	}

	assertType('array<int, 1|string>', $arr);
}

class Foo
{
	/** @var array<string, int> */
	private array $prop;

	/**
	 * By-ref without key on a property.
	 */
	public function byRefWithoutKeyProperty(): void
	{
		foreach ($this->prop as &$v) {
			$v = 'hello';
		}

		assertType("array<string, 'hello'>", $this->prop);
	}
}

/**
 * By-ref without key with intval transformation.
 * @param array<int, string> $arr
 */
function byRefWithoutKeyTransform(array $arr): void
{
	foreach ($arr as &$v) {
		$v = intval($v);
	}

	assertType('array<int, int>', $arr);
}

/**
 * By-ref without key — value not overwritten at all.
 * @param array<int, string> $arr
 */
function byRefWithoutKeyNoOverwrite(array $arr): void
{
	foreach ($arr as &$v) {
		// just read, don't write
		echo $v;
	}

	assertType('array<int, string>', $arr);
}
