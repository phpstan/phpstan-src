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

function pregMatchNotIdentical(string $x): void {
	if (preg_match('#ExtensionInterface$#', $x) !== 1) {
		return;
	}
	assertType('non-falsy-string', $x);
}

function pregMatchNotEqual(string $x): void {
	if (preg_match('#ExtensionInterface$#', $x) != 1) {
		return;
	}
	assertType('non-falsy-string', $x);
}

function pregMatchWithNonConstantPattern(string $pattern, string $x): void {
	if (preg_match($pattern, $x)) {
		assertType('string', $x);
	}
}

function pregMatchSubjectSharesVarWithMatches(): void {
	$matches = ['', '', 'foo'];
	if (preg_match('/^(a|b|c)$/', $matches[2], $matches)) {
		assertType("array{non-falsy-string, 'a'|'b'|'c'}", $matches);
	}
}

function pregMatchNullableSubject(?string $x): void {
	// a null subject is coerced to '' which cannot match a non-empty pattern, so null is removed
	if (preg_match('/^(a|b|c)$/', $x)) {
		assertType('string|null', $x); // could be non-falsy-string
	} else {
		assertType('string|null', $x);
	}
}

function pregMatchIntStringSubject(int|string $x): void {
	// an int subject can be coerced to a matching string, so narrowing it away would be unsound
	if (preg_match('/^(a|b|c)$/', $x)) {
		assertType('int|string', $x);
	}
}
