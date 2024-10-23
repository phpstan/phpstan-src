<?php

namespace ParamPureUnlessCallableIsImpure;

use function PHPStan\Testing\assertType;

/**
 * @template TValue
 * @template TResult
 * @param Closure(TValue): TResult $f
 * @param iterable<TValue> $a
 * @return array<TResult>
 * @pure-unless-callable-is-impure $f
 */
function map(Closure $f, iterable $a): array
{
	$result = [];
	foreach ($a as $i => $v) {
		$retult[$i] = $f($v);
	}

	return $result;
}

map('printf', []);
map('sprintf', []);

assertType('array', map('printf', []));
