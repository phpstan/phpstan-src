<?php

namespace PreciseScopeSelectFromArgs;

use stdClass;
use function PHPStan\Testing\assertType;

/**
 * @template T of object
 * @param mixed $foo
 * @param T $in
 * @return T
 */
function doFoo($foo, object $in): object
{
	return $in;
}

function (): void {
	$r = doFoo($a = new stdClass(), $a);
	assertType(stdClass::class, $r);
};

function (): void {
	$a = new stdClass();
	$r = doFoo($a, $a);
	assertType(stdClass::class, $r);
};
