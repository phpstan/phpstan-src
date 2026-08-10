<?php declare(strict_types = 1);

namespace Bug15060;

use function PHPStan\Testing\assertType;

function search($searchParams): void
{
	if (isset($searchParams['test'])) {
		assertType('mixed~null', $searchParams['test']);
		if ($searchParams["test"]) {
			assertType("mixed~(0|0.0|''|'0'|array{}|false|null)", $searchParams['test']);
			assertType("mixed~(0|0.0|''|'0'|array{}|false|null)", $searchParams["test"]);
		}

		if (is_array($searchParams["test"])) {
			assertType('array<mixed, mixed>', $searchParams['test']);
			assertType('array<mixed, mixed>', $searchParams["test"]);
		}

		if (is_array($searchParams["test"]) && $searchParams["test"]) {
			assertType('non-empty-array<mixed, mixed>', $searchParams['test']);
			assertType('non-empty-array<mixed, mixed>', $searchParams["test"]);
		}
	}
}

function otherStringSpellings($m): void
{
	if (is_array($m['test']) && $m['test']) {
		assertType('non-empty-array<mixed, mixed>', $m['test']);
		assertType('non-empty-array<mixed, mixed>', $m["test"]);
		assertType('non-empty-array<mixed, mixed>', $m["\x74est"]);
		assertType('non-empty-array<mixed, mixed>', $m[<<<'NOWDOC'
			test
			NOWDOC]);
		assertType('non-empty-array<mixed, mixed>', $m[<<<HEREDOC
			test
			HEREDOC]);
	}
}

function intSpellings($m): void
{
	if (is_array($m[1]) && $m[1]) {
		assertType('non-empty-array<mixed, mixed>', $m[1]);
		assertType('non-empty-array<mixed, mixed>', $m[0x1]);
		assertType('non-empty-array<mixed, mixed>', $m[01]);
		assertType('non-empty-array<mixed, mixed>', $m[0b1]);
		assertType('mixed', $m[10]);
	}
}

function interpolatedSpellings($m, string $k): void
{
	if (is_array($m["x$k"]) && $m["x$k"]) {
		assertType('non-empty-array<mixed, mixed>', $m["x$k"]);
		assertType('non-empty-array<mixed, mixed>', $m["x{$k}"]);
		assertType('non-empty-array<mixed, mixed>', $m[<<<HEREDOC
			x$k
			HEREDOC]);
	}
}
