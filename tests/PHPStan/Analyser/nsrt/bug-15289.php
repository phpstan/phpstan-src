<?php // lint >= 8.0

namespace Bug15289;

use LogicException;
use function PHPStan\Testing\assertType;

/**
 * @template-covariant T
 * @phpstan-sealed Some|None
 */
interface Option {}

/**
 * @template-covariant T
 * @implements Option<T>
 */
final class Some implements Option {}

/** @implements Option<never> */
final class None implements Option {}

/**
 * @template-covariant T
 * @template-covariant E
 * @phpstan-sealed Ok|Err
 */
interface Result {}

/**
 * @template-covariant T
 * @implements Result<T, never>
 */
final class Ok implements Result {}

/**
 * @template-covariant E
 * @implements Result<never, E>
 */
final class Err implements Result {}

/**
 * @param Option<int> $o
 * @return Option<string>
 */
function afterEmptyBranch(Option $o): Option
{
	if ($o instanceof Some) {
	}
	assertType('Bug15289\None|Bug15289\Some<int>', $o);

	return $o; // should be reported
}

/**
 * @param Option<int> $o
 * @return Option<string>
 */
function inTheBranch(Option $o): Option
{
	if ($o instanceof Some) {
		assertType('Bug15289\Some<int>', $o);

		return $o; // should be reported
	}

	return $o; // correctly not reported: $o is None here
}

/**
 * @param Result<int, string> $r
 * @return Err<int>
 */
function elseBranch(Result $r): Err
{
	if ($r instanceof Ok) {
		throw new LogicException();
	}
	assertType('Bug15289\Err<string>', $r);

	return $r; // should be reported
}

/** @param Option<int> $o */
function exhaustiveMatch(Option $o): int
{
	return match (true) {
		$o instanceof Some => 1,
		$o instanceof None => 2,
	};
}

/** @template-covariant T */
interface UnsealedOption {}

/**
 * @template-covariant T
 * @implements UnsealedOption<T>
 */
final class UnsealedSome implements UnsealedOption {}

/**
 * @param UnsealedOption<int> $o
 * @return UnsealedOption<string>
 */
function unsealedControl(UnsealedOption $o): UnsealedOption
{
	if ($o instanceof UnsealedSome) {
		assertType('Bug15289\UnsealedSome<int>', $o);
	}
	assertType('Bug15289\UnsealedOption<int>', $o);

	return $o; // reported
}

/** @param Result<int, string> $r */
function truthyBranchOfEachVariant(Result $r): void
{
	if ($r instanceof Err) {
		assertType('Bug15289\Err<string>', $r);
	} else {
		assertType('Bug15289\Ok<int>', $r);
	}
}

/**
 * @template T
 * @param Option<T> $o
 */
function templateArgument(Option $o): void
{
	assertType('Bug15289\Option<T (function Bug15289\templateArgument(), argument)>', $o);
	if ($o instanceof Some) {
		assertType('Bug15289\Some<T (function Bug15289\templateArgument(), argument)>', $o);
	}
	assertType('Bug15289\None|Bug15289\Some<T (function Bug15289\templateArgument(), argument)>', $o);
}

/** @template T */
interface Invariant {}

/**
 * @template T
 * @implements Invariant<T>
 */
final class InvariantImpl implements Invariant {}

/** @param Invariant<covariant int> $i */
function callSiteVariance(Invariant $i): void
{
	if ($i instanceof InvariantImpl) {
		assertType('Bug15289\InvariantImpl', $i);
	}
}

/** @phpstan-assert Some $o */
function assertSome(Option $o): void
{
}

/** @param Option<int>|null $o */
function otherNarrowings(?Option $o): void
{
	if ($o instanceof Some) {
		assertType('Bug15289\Some<int>', $o);
	}

	if ($o === null) {
		return;
	}

	if (is_a($o, Some::class)) {
		assertType('Bug15289\Some<int>', $o);
	}

	if ($o::class === Some::class) {
		assertType('Bug15289\Some<int>', $o);
	}

	assertSome($o);
	assertType('Bug15289\Some<int>', $o);
}

/**
 * @template-covariant T
 * @phpstan-sealed Plain|Tagged
 */
interface Wrapped {}

/**
 * @template-covariant T
 * @implements Wrapped<T>
 */
final class Plain implements Wrapped {}

/**
 * @template-covariant T
 * @template-covariant Tag
 * @implements Wrapped<T>
 */
final class Tagged implements Wrapped {}

/** @param Wrapped<int> $w */
function undeterminedArgument(Wrapped $w): void
{
	if ($w instanceof Tagged) {
		assertType('Bug15289\Tagged', $w);
	}
}

/** @param Wrapped<int> $w */
function undeterminedRemainder(Wrapped $w): void
{
	if ($w instanceof Plain) {
		assertType('Bug15289\Plain<int>', $w);
		return;
	}

	assertType('Bug15289\Tagged', $w);
}

function bareSupertype(UnsealedOption $o): void
{
	assertType('Bug15289\UnsealedOption', $o);
	if ($o instanceof UnsealedSome) {
		assertType('Bug15289\UnsealedSome', $o);
	}
}

/**
 * @template O of Option<int>
 * @param O $o
 */
function templateRemainder(Option $o): void
{
	if ($o instanceof None) {
		return;
	}

	assertType('O of Bug15289\Some<int> (function Bug15289\templateRemainder(), argument)', $o);
}
