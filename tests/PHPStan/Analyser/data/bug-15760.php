<?php declare(strict_types = 1);

namespace Bug15760;

use function PHPStan\Testing\assertType;

function f(string $unit, bool $blur): string
{
	$units = $blur ? ['px', 'rem'] : ['px', 'rem', '%'];

	if (!in_array($unit, $units, true)) {
		return 'none';
	}

	assertType('bool', $blur);

	return $blur ? 'blur' : 'distance';
}

function reversedArms(string $unit, bool $blur): void
{
	$units = $blur ? ['px', 'rem', '%'] : ['px', 'rem'];
	if (!in_array($unit, $units, true)) {
		return;
	}
	assertType('bool', $blur);
}

function stringKeys(string $unit, bool $blur): void
{
	$units = $blur ? ['a' => 'px'] : ['a' => 'px', 'b' => 'rem'];
	if (!in_array($unit, $units, true)) {
		return;
	}
	assertType('bool', $blur);
}

function ifElse(string $unit, bool $blur): void
{
	if ($blur) {
		$units = ['px', 'rem'];
	} else {
		$units = ['px', 'rem', '%'];
	}
	if (!in_array($unit, $units, true)) {
		return;
	}
	assertType('bool', $blur);
}

function matchArms(string $unit, int $i): void
{
	$units = match ($i) {
		1 => ['px', 'rem'],
		default => ['px', 'rem', '%'],
	};
	if (!in_array($unit, $units, true)) {
		return;
	}
	assertType('int', $i);
}

function disjointArmsStillNarrow(bool $blur): void
{
	$units = $blur ? ['px', 'rem'] : ['em'];
	if ($units === ['em']) {
		assertType('false', $blur);
	} else {
		assertType('true', $blur);
	}
}
