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

function noErrorEnumNeedle(Foo $foo): void
{
	if ($foo !== Foo::ONE) {
		return;
	}

	// Foo::TWO is just another case of the same enum - looking one case up in a
	// set of cases is idiomatic, not a mistake.
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

enum Param
{

	case UtmSource;
	case UtmMedium;
	case Gclid;
	case Dclid;

	public const ALL = [self::UtmSource, self::UtmMedium, self::Gclid, self::Dclid];

}

enum DiscrepancyType
{

	case LineOverReceive;
	case SkuOverReceive;
	case LineUnderReceive;

	public const OVER_RECEIVE = [self::LineOverReceive, self::SkuOverReceive];
	public const LINE_LEVEL = [self::LineOverReceive, self::LineUnderReceive];

}

enum Bar
{

	case ONE;
	case TWO;

}

/**
 * @return array<Param>
 */
function noErrorRemoveKnownCaseFromConstantList(): array
{
	$params = Param::ALL;
	$key = array_search(Param::Dclid, $params, true);
	if ($key !== false) {
		unset($params[$key]);
	}

	return $params;
}

function noErrorOverlappingConstantSets(): void
{
	foreach (DiscrepancyType::OVER_RECEIVE as $type) {
		if (in_array($type, DiscrepancyType::LINE_LEVEL, true)) {
			echo 'line level';
		}
	}
}

function noErrorArrayKeysOfConstantSet(): void
{
	array_keys(Param::ALL, Param::Dclid, true);
}

/**
 * @param 'a'|'b' $s
 */
function noErrorConstantStringSet(string $s): void
{
	if (in_array($s, ['a', 'b', 'c'], true)) {
		echo 'yes';
	}
}

/**
 * @param 1|2 $i
 */
function noErrorConstantIntegerSet(int $i): void
{
	if (in_array($i, [1, 2, 3], true)) {
		echo 'yes';
	}
}

/**
 * @param 'a'|'b' $s
 */
function noErrorConstantStringSetLoose(string $s): void
{
	if (in_array($s, ['a', 'b', 'c'])) {
		echo 'yes';
	}
}

function noErrorConstantFloatSet(float $f): void
{
	if ($f !== 1.0) {
		return;
	}

	if (in_array($f, [1.0, 2.0], true)) {
		echo 'yes';
	}
}

function noErrorConstantBooleanSet(bool $b): void
{
	if ($b !== true) {
		return;
	}

	if (in_array($b, [true, false], true)) {
		echo 'yes';
	}
}

function reportedForeignEnumInHaystack(Foo $foo): void
{
	// Bar::ONE is a case of a different enum - it can never be a Foo.
	if (in_array($foo, [Foo::ONE, Bar::ONE], true)) {
		echo 'yes';
	}
}

function reportedStringInIntegerHaystack(int $i): void
{
	if (in_array($i, [1, 2, '3'], true)) {
		echo 'yes';
	}
}

function reportedNullInHaystack(?string $s): void
{
	if ($s === null) {
		return;
	}

	if (in_array($s, [null, 'a'], true)) {
		echo 'yes';
	}
}

function reportedArraySearchNothingMatches(): void
{
	// Nothing in the haystack can match - array_search() has no companion rule
	// reporting the whole call, so the values are still worth reporting.
	array_search(Param::Dclid, [Param::Gclid, Param::UtmSource], true);
}

enum HttpStatus: int
{

	case Ok = 200;
	case NotFound = 404;
	case ServerError = 500;

}

function reportedBackedEnumWithoutValue(int $code): void
{
	// HttpStatus::ServerError is missing ->value, so the 500 branch never fires.
	if (in_array($code, [HttpStatus::Ok->value, HttpStatus::NotFound->value, HttpStatus::ServerError], true)) {
		echo 'yes';
	}
}

/**
 * @param int<1, 5> $i
 */
function noErrorIntegerRangeNeedle(int $i): void
{
	if (in_array($i, [1, 2, 10], true)) {
		echo 'yes';
	}
}
