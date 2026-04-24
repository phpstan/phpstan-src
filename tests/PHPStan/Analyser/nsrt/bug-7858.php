<?php declare(strict_types = 1);

namespace Bug7858;

use function PHPStan\Testing\assertType;

function foo(int $year): void
{
	if (!ctype_digit($year) || (int) $year < 2022) {
		throw new \RuntimeException();
	}
	assertType('int<2022, max>', $year);
	assertType('int<2022, max>', (int) $year);
}

function bar(int $year): void
{
	if (!ctype_digit($year) || $year < 2022) {
		throw new \RuntimeException();
	}
	assertType('int<2022, max>', $year);
}

function baz($year): void
{
	if (!ctype_digit($year) || (int)$year < 2022) {
		throw new \RuntimeException();
	}
	assertType('int<2022, max>|numeric-string', $year);
	assertType('int<2022, max>', (int) $year);
}

function bam(int|string $year): void
{
	if (!ctype_digit($year) || (int)$year < 2022) {
		throw new \RuntimeException();
	}
	assertType('int<2022, max>|numeric-string', $year);
	assertType('int<2022, max>', (int) $year);
}

function ban(string $year): void
{
	if (!ctype_digit($year) || (int)$year < 2022) {
		throw new \RuntimeException();
	}
	assertType('numeric-string', $year);
	assertType('int<2022, max>', (int) $year);
}

function bak($mixed): void
{
	if (!is_numeric($mixed) || (int)$mixed < 2022) {
		throw new \RuntimeException();
	}
	assertType('float|int<2022, max>|numeric-string', $mixed);
	assertType('int<2022, max>', (int) $mixed);
}
