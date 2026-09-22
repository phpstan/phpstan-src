<?php declare(strict_types = 1);

namespace RandSwappedArguments;

use function PHPStan\Testing\assertType;

function doFoo(int $i): void
{
	assertType('int<1, 5>', rand(5, 1));
	assertType('int<-5, 5>', rand(5, -5));
	assertType('int<1, 10>', rand(10, random_int(1, 5)));

	\assert($i >= 0);
	assertType('int<0, max>', rand($i, 0));

	assertType('*NEVER*', mt_rand(5, 1));
	assertType('*NEVER*', random_int(5, 1));
}
