<?php declare(strict_types = 1);

namespace Bug14766;

use function PHPStan\Testing\assertType;

function pregMatchNonDecimalIntStringTypeSubject(string $x): void
{
	if (preg_match('/^-?[0-9]+$/', $x)) {
		assertType('decimal-int-string', $x);
	} else {
		assertType('non-decimal-int-string', $x);
	}
}

function negatedCondition(string $x): void
{
	if (!preg_match('/^[0-9]+$/', $x)) {
		assertType('non-decimal-int-string', $x);
	} else {
		assertType('decimal-int-string', $x);
	}
}

function earlyReturn(string $x): void
{
	if (preg_match('/^[0-9]+$/', $x)) {
		return;
	}

	assertType('non-decimal-int-string', $x);
}

function withMatchesArg(string $x): void
{
	if (preg_match('/^([0-9]+)$/', $x, $matches)) {
		assertType('decimal-int-string', $x);
		assertType('array{decimal-int-string, decimal-int-string}', $matches);
	} else {
		assertType('non-decimal-int-string', $x);
		assertType('array{}', $matches);
	}
}

function unanchoredIsNotNarrowedInElse(string $x): void
{
	// an unanchored pattern only proves the subject contains a digit when it
	// matches; not matching tells us nothing representable, so no narrowing
	if (preg_match('/[0-9]/', $x)) {
		assertType('non-empty-string', $x);
	} else {
		assertType('string', $x);
	}
}

function nonEmptySubjectIsNotNarrowedInElse(string $x): void
{
	// the complement of non-empty-string within string is not representable,
	// so the else branch keeps the original string type
	if (preg_match('/^\S+$/', $x)) {
		assertType('non-empty-string', $x);
	} else {
		assertType('string', $x);
	}
}
