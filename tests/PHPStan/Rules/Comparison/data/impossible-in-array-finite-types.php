<?php // lint >= 8.1

declare(strict_types = 1);

namespace ImpossibleInArrayFiniteTypes;

enum Foo
{

	case ONE;
	case TWO;
	case THREE;

}

function reportedFiniteValueStrict(int $i): void
{
	// Foo::ONE can never be an int, but 1 and 2 can.
	if (in_array($i, [Foo::ONE, 1, 2], true)) {
		echo 'yes';
	}
}

function reportedFiniteValueLoose(int $i): void
{
	if (in_array($i, [Foo::ONE, 1, 2])) {
		echo 'yes';
	}
}

function reportedArraySearch(int $i): void
{
	array_search($i, [Foo::ONE, 1, 2], true);
}

function reportedArrayKeys(int $i): void
{
	array_keys([Foo::ONE, 1, 2], $i, true);
}

function reportedEnumNeedle(Foo $foo): void
{
	if ($foo !== Foo::ONE) {
		return;
	}

	// Foo::TWO can never be Foo::ONE (finite value in haystack), but Foo::ONE can.
	if (in_array($foo, [Foo::ONE, Foo::TWO], true)) {
		echo 'yes';
	}
}

function noErrorEverythingMatches(int $i): void
{
	if (in_array($i, [1, 2, 3], true)) {
		echo 'yes';
	}
}

function noErrorMixedNeedle(mixed $i): void
{
	if (in_array($i, [Foo::ONE, 1, 2], true)) {
		echo 'yes';
	}
}

function noErrorWholeCallImpossible(int $i): void
{
	// Whole call is impossible - reported by ImpossibleCheckTypeFunctionCallRule instead.
	if (in_array($i, [Foo::ONE, Foo::TWO], true)) {
		echo 'yes';
	}
}

function noErrorNonConstantHaystack(int $i, array $haystack): void
{
	if (in_array($i, $haystack, true)) {
		echo 'yes';
	}
}

/**
 * @param int|string $i
 */
function noErrorUnionNeedleMatches(int|string $i): void
{
	if (in_array($i, ['a', 'b', 1], true)) {
		echo 'yes';
	}
}

function reportedValueEliminatedFromNeedle(mixed $status): void
{
	if ($status === 'installed') {
		return;
	}

	if (in_array($status, ['installed', 'active'], true)) {
		echo 'yes';
	}
}
