<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14926;

use function PHPStan\Testing\assertType;

function test_if(?string $x): void
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

function test_match(?string $x): void
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
