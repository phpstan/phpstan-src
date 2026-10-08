<?php declare(strict_types = 1);

namespace ArrayFilterTypedCallbackParameter;

use function PHPStan\Testing\assertType;

/**
 * @param array<mixed> $trace
 * @param array{a: array{file: string}|int, b: array{line: int}, c: mixed} $shape
 */
function narrowsTheElementNotTheDeclaredParameter(array $trace, array $shape): void
{
	assertType('list<mixed~null>', array_values(array_filter($trace, fn (array $frame) => isset($frame['file']))));
	assertType('array<mixed~null>', array_filter($trace, function (array $frame) {
		return isset($frame['file']);
	}));
	assertType('array<mixed~null>', array_filter($trace, fn ($frame) => isset($frame['file'])));
	assertType('array<mixed~null>', array_filter($trace, fn (array $frame) => !empty($frame['file'])));
	assertType('array{a?: array{file: string}, c?: mixed~null}', array_filter($shape, fn (array $t) => isset($t['file'])));
}
