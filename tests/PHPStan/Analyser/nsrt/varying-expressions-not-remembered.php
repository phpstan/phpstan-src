<?php declare(strict_types = 1);

namespace VaryingExpressionsNotRemembered;

use function PHPStan\Testing\assertType;

/**
 * @param \Generator<int, int, int, int> $inner
 * @param array<int, int> $arr
 * @return \Generator<int, int, int, int>
 */
function yields(\Generator $inner, array $arr): \Generator
{
	if ((yield 1) === 5) {
		assertType('int', yield 1);
	}
	if (yield 1) {
		assertType('int', yield 1);
	}
	if ((yield from $inner) === 5) {
		assertType('int', yield from $inner);
	}
	if ($arr[yield 1] === 5) {
		assertType('int', $arr[yield 1]);
	}

	return 1;
}

function shellExec(): void
{
	if (`ls` === 'a') {
		assertType('string|false|null', `ls`);
	}
	if (`ls`) {
		assertType('string|false|null', `ls`);
	}
	if ('a' . `ls` === 'ab') {
		assertType('non-falsy-string', 'a' . `ls`);
	}
}
