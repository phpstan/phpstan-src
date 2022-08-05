<?php declare(strict_types=1);

namespace Bug5287;

use function PHPStan\Testing\assertType;

/**
 * @param list<mixed> $arr
 */
function foo(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('array<int, mixed>', $arrSpread);
}

/**
 * @param list<non-empty-array> $arr
 */
function foo2(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('array<int, non-empty-array>', $arrSpread);
}

/**
 * @param non-empty-list<string> $arr
 */
function foo3(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('non-empty-array<int, string>', $arrSpread);
}

/**
 * @param non-empty-array<string, int> $arr
 */
function foo3(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('non-empty-array<int, int>', $arrSpread);
}

/**
 * @param non-empty-array<mixed, bool|int> $arr
 */
function foo4(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('non-empty-array<int, bool|int>', $arrSpread);
}

/**
 * @param array{foo: 17, bar: 19} $arr
 */
function bar(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('array{17, 19}', $arrSpread);
}
