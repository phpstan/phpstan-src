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

function baz($year): void
{
	if (!ctype_digit($year) || (int)$year < 2022) {
		throw new \RuntimeException();
	}
	assertType('int<2022, max>|numeric-string', $year);
}

function bam(int|string $year): void
{
	if (!ctype_digit($year) || (int)$year < 2022) {
		throw new \RuntimeException();
	}
	assertType('int<2022, max>|numeric-string', $year);
}

function ban(string $year): void
{
	if (!ctype_digit($year) || (int)$year < 2022) {
		throw new \RuntimeException();
	}
	assertType('numeric-string', $year);
}

function bak($mixed): void
{
	if (!is_numeric($mixed) || !(bool)$mixed) {
		throw new \RuntimeException();
	}
	assertType("float|int<min, -1>|int<1, max>|(non-falsy-string&numeric-string)", $mixed);
}

function bal($mixed): void
{
	if (!is_numeric($mixed) || (bool)$mixed) {
		throw new \RuntimeException();
	}
	assertType("0|0.0|'0'", $mixed);
}

function bau($mixed): void
{
	if (!is_numeric($mixed) || (string)$mixed) {
		throw new \RuntimeException();
	}
	assertType("0|0.0", $mixed);
}

function baw($mixed): void
{
	if (!is_numeric($mixed) || !(string)$mixed) {
		throw new \RuntimeException();
	}
	assertType("float|int|numeric-string", $mixed);
}
