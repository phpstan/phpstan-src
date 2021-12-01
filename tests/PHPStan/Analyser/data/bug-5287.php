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
 * @param array{foo: 17, bar: 19} $arr
 */
function bar(array $arr): void
{
	$arrSpread = [...$arr];
	assertType('array{17, 19}', $arrSpread);
}
