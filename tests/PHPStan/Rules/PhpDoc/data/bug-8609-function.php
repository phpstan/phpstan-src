<?php

namespace Bug8609Function;

use function PHPStan\Testing\assertType;

/**
 * @template T of list<string>|list<list<string>>
 * @param T $bar
 *
 * @return (T[0] is string ? array{T} : T)
 */
function foo(array $bar) : array{ return is_string($bar[0]) ? [$bar] : $bar; }

function(): void {
	assertType('array{array{string, string}}', foo(['foo', 'bar']));
	assertType('array{array{string, string}, array{string, string}}', foo([['foo','bar'],['xyz','asd']]));
};
