<?php declare(strict_types=1);

namespace Bug7809;

use function array_search;
use function PHPStan\Testing\assertType;

function foo(bool $strict = false): void {
	assertType('0|1|2|false', array_search('c', ['a', 'b', 'c'], $strict));
}

function bar(): void{
	assertType('2', array_search('c', ['a', 'b', 'c'], true));
}

function baz(): void{
	assertType('0|1|2|false', array_search('c', ['a', 'b', 'c'], false));
}
