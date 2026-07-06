<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14926;

use function PHPStan\Testing\assertType;

function testIf(?string $x): void
{
	if ($x === 'aa') {
		$class = ['some_class'];
	} elseif ($x === 'bb' || $x === 'cc') {
		$class = ['another_class'];
	} else {
		$class = null;
	}

	if ($class === null) {
		return;
	}

	assertType("'aa'|'bb'|'cc'", $x);
}

function testMatch(?string $x): void
{
	$class = match ($x) {
		'aa' => ['some_class'],
		'bb', 'cc' => ['another_class'],
		default => null,
	};

	if ($class === null) {
		return;
	}

	assertType("'aa'|'bb'|'cc'", $x);
}

function testNestedArrayGuard(?string $x): void
{
	if ($x === 'aa') {
		$class = ['a' => 1, 'b' => 2];
	} elseif ($x === 'bb') {
		$class = ['a' => 3, 'b' => 4];
	} else {
		$class = null;
	}

	if ($class === null) {
		return;
	}

	assertType("'aa'|'bb'", $x);
}
