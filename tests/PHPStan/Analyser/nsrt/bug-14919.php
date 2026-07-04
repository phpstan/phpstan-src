<?php declare(strict_types = 1);

namespace Bug14919;

use function PHPStan\Testing\assertType;

/**
 * Deep `!==` chains dispatch specifyTypesInCondition() once per arm through the
 * ExprHandler lookup. This locks in the narrowing produced by that dispatch so
 * the per-class handler memoization keeps behaving identically.
 *
 * @param 'a'|'b'|'c'|'d'|'e' $x
 */
function deepNotIdenticalAnd(string $x): void
{
	if (
		$x !== 'a' &&
		$x !== 'b' &&
		$x !== 'c' &&
		$x !== 'd' &&
		$x !== 'e'
	) {
		assertType('*NEVER*', $x);
	} else {
		assertType("'a'|'b'|'c'|'d'|'e'", $x);
	}
}

/**
 * @param 'a'|'b'|'c'|'d'|'e' $x
 */
function deepIdenticalOr(string $x): void
{
	if (
		$x === 'a' ||
		$x === 'b' ||
		$x === 'c' ||
		$x === 'd' ||
		$x === 'e'
	) {
		assertType("'a'|'b'|'c'|'d'|'e'", $x);
	} else {
		assertType('*NEVER*', $x);
	}
}

function deepNotIdenticalAndKeepsString(string $tag): void
{
	$x = (
		'ADDRESS' !== $tag &&
		'APPLET' !== $tag &&
		'AREA' !== $tag &&
		'ARTICLE' !== $tag &&
		'ASIDE' !== $tag &&
		'BASE' !== $tag &&
		'BASEFONT' !== $tag
	);

	assertType('bool', $x);
	if ($x) {
		assertType('string', $tag);
	}
}
