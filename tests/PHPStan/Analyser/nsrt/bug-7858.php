<?php declare(strict_types = 1);

namespace Bug7858;

use function PHPStan\Testing\assertType;

function foo(int $year): void
{
	if (!ctype_digit($year) || (int) $year < 2022) {
		throw new \RuntimeException();
	}
	assertType('int<2022, max>', $year);
}

function bar(int $year): void
{
	if (!ctype_digit($year) || $year < 2022) {
		throw new \RuntimeException();
	}
	assertType('int<2022, max>', $year);
}
