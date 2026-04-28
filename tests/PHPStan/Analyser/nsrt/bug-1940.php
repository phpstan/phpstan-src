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

	public function byRefWithoutKeyProperty(): void
	{
		foreach ($this->prop as &$v) {
			$v = 'hello';
		}

		assertType("array<string, 'hello'>", $this->prop);
	}
}

/**
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
 * @param array<int, string> $arr
 */
function byRefWithoutKeyNoOverwrite(array $arr): void
{
	foreach ($arr as &$v) {
		echo $v;
	}

	assertType('array<int, string>', $arr);
}

/**
 * @param list<array{one: string}> $a
 */
function byRefWithKeyModifySubElement(array $a): void
{
	foreach ($a as $k => &$testArray) {
		$testArray['two'] = 'two';
	}

	assertType("list<array{one: string, two: 'two'}>", $a);
}

/**
 * @param list<array{one: string}> $a
 */
function byRefWithoutKeyModifySubElement(array $a): void
{
	foreach ($a as &$testArray) {
		$testArray['two'] = 'two';
	}

	assertType("list<array{one: string, two: 'two'}>", $a);
}

/**
 * @param array<int, string> $arr
 */
function byRefWithKeyDirectOverwrite(array $arr): void
{
	foreach ($arr as $k => &$v) {
		$v = 1;
	}

	assertType('array<int, 1>', $arr);
}

/**
 * @param array<int, string> $arr
 */
function byRefWithoutKeyDirectOverwrite(array $arr): void
{
	foreach ($arr as &$v) {
		$v = 1;
	}

	assertType('array<int, 1>', $arr);
}
