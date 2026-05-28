<?php declare(strict_types = 1);

namespace Bug14710;

use function PHPStan\Testing\assertType;

function ternaryPregMatch(string $x): void {
	(preg_match('/^(a|b|c)$/', $x)) ?
		assertType('non-falsy-string', $x)
		: assertType('string', $x);
}

function ifPregMatch(string $x): void {
	if (preg_match('/^(a|b|c)$/', $x)) {
		assertType('non-falsy-string', $x);
	} else {
		assertType('string', $x);
	}
}

function ternaryPregMatchWithMatches(string $x): void {
	(preg_match('/^(a|b|c)$/', $x, $matches)) ?
		assertType('non-falsy-string', $x)
		: assertType('string', $x);
}

function ifPregMatchWithMatches(string $x): void {
	if (preg_match('/^(a|b|c)$/', $x, $matches)) {
		assertType('non-falsy-string', $x);
	} else {
		assertType('string', $x);
	}
}

function pregMatchNonEmpty(string $x): void {
	if (preg_match('/foo/', $x)) {
		assertType('non-falsy-string', $x);
	}
}

function pregMatchNoNarrow(string $x): void {
	if (preg_match('/^$/', $x)) {
		assertType('string', $x);
	}
}

function pregMatchAll(string $x): void {
	if (preg_match_all('/^(a|b|c)$/', $x)) {
		assertType('non-falsy-string', $x);
	} else {
		assertType('string', $x);
	}
}

function negatedPregMatch(string $x): void {
	if (!preg_match('/^(a|b|c)$/', $x)) {
		assertType('string', $x);
	} else {
		assertType('non-falsy-string', $x);
	}
}

function pregMatchCompare(string $x): void {
	if (preg_match('/^(a|b|c)$/', $x) === 1) {
		assertType('non-falsy-string', $x);
	}
}

function pregMatchNoSubject(): void {
	if (preg_match('/^(a|b|c)$/', 'a')) {
		// non-variable subject, no narrowing needed
	}
}

function pregMatchWithNonConstantPattern(string $pattern, string $x): void {
	if (preg_match($pattern, $x)) {
		assertType('string', $x);
	}
}
