<?php // lint < 8.1

declare(strict_types=1);

namespace Bug5287;

use function PHPStan\Testing\assertType;

/**
 * @param list<mixed> $arr
 */
function foo(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('list<mixed>', $arrSpread);
}

/**
 * @param list<non-empty-array> $arr
 */
function foo2(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('list<non-empty-array>', $arrSpread);
}

/**
 * @param non-empty-list<string> $arr
 */
function foo3(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('non-empty-list<string>', $arrSpread);
}

/**
 * @param non-empty-array<string, int> $arr
 */
function foo4(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('non-empty-list<int>', $arrSpread);
}

/**
 * @param non-empty-array<mixed, bool|int> $arr
 */
function foo5(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('non-empty-list<bool|int>', $arrSpread);
}

/**
 * @param array{foo: 17, bar: 19} $arr
 */
function bar(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('array{17, 19}', $arrSpread);
}
