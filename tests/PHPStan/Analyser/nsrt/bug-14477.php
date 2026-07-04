<?php declare(strict_types = 1);

namespace Bug14477;

use function PHPStan\Testing\assertType;

final class C1 {}
final class C2 {}
final class C3 {}
final class C4 {}
final class C5 {}
final class C6 {}
final class C7 {}
final class C8 {}

// A BooleanOr chain deeper than BOOLEAN_EXPRESSION_MAX_PROCESS_DEPTH takes the flattened
// resolveType() path; its boolean type and the truthy-branch narrowing must match the recursive one.
function test(object $x): void
{
	$result = $x instanceof C1
		|| $x instanceof C2
		|| $x instanceof C3
		|| $x instanceof C4
		|| $x instanceof C5
		|| $x instanceof C6
		|| $x instanceof C7
		|| $x instanceof C8;
	assertType('bool', $result);

	if (
		$x instanceof C1
		|| $x instanceof C2
		|| $x instanceof C3
		|| $x instanceof C4
		|| $x instanceof C5
		|| $x instanceof C6
		|| $x instanceof C7
		|| $x instanceof C8
	) {
		assertType('Bug14477\\C1|Bug14477\\C2|Bug14477\\C3|Bug14477\\C4|Bug14477\\C5|Bug14477\\C6|Bug14477\\C7|Bug14477\\C8', $x);
	}
}
