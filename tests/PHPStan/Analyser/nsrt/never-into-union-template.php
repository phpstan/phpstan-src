<?php declare(strict_types = 1);

namespace NeverIntoUnionTemplate;

use Traversable;
use function PHPStan\Testing\assertType;

/** @template-covariant T */
interface P
{

}

/**
 * @template T
 * @param callable(): (T|null) $cb
 * @return P<T>
 */
function tOrNull(callable $cb): P
{
	throw new \Exception();
}

/**
 * @template T
 * @param callable(): (T|int|float) $cb
 * @return P<T>
 */
function tOrIntFloat(callable $cb): P
{
	throw new \Exception();
}

/**
 * @template T
 * @param callable(): (array<T>|T) $cb
 * @return P<T>
 */
function arrayOrT(callable $cb): P
{
	throw new \Exception();
}

/**
 * @template T
 * @param callable(): (iterable<T>|T) $cb
 * @return P<T>
 */
function iterableOrT(callable $cb): P
{
	throw new \Exception();
}

/**
 * @template T
 * @param callable(): (Traversable<T>|T) $cb
 * @return P<T>
 */
function traversableOrT(callable $cb): P
{
	throw new \Exception();
}

/**
 * @template T
 * @param callable(): (P<T>|T) $cb
 * @return P<T>
 */
function pOrT(callable $cb): P
{
	throw new \Exception();
}

/**
 * @template T
 * @param callable(): iterable<T> $cb
 * @return P<T>
 */
function iterable(callable $cb): P
{
	throw new \Exception();
}

/**
 * @template T
 * @param P<P<T>|T> $p
 * @return P<T>
 */
function nestedInGeneric(P $p): P
{
	throw new \Exception();
}

/**
 * @template T
 * @param array{P<T>|T} $a
 * @return P<T>
 */
function nestedInArrayShape(array $a): P
{
	throw new \Exception();
}

/**
 * @param P<never> $pNever
 * @param array{never} $aNever
 */
function test(P $pNever, array $aNever): void
{
	$throwing = static function (): void {
		throw new \Exception();
	};

	assertType('NeverIntoUnionTemplate\\P<never>', tOrNull($throwing));
	assertType('NeverIntoUnionTemplate\\P<never>', tOrIntFloat($throwing));
	assertType('NeverIntoUnionTemplate\\P<never>', arrayOrT($throwing));
	assertType('NeverIntoUnionTemplate\\P<never>', iterableOrT($throwing));
	assertType('NeverIntoUnionTemplate\\P<never>', traversableOrT($throwing));
	assertType('NeverIntoUnionTemplate\\P<never>', pOrT($throwing));

	// no union involved: consistent with array<T> and Traversable<T>, which
	// infer nothing from never either
	assertType('NeverIntoUnionTemplate\\P<mixed>', iterable($throwing));

	assertType('NeverIntoUnionTemplate\\P<never>', nestedInGeneric($pNever));
	assertType('NeverIntoUnionTemplate\\P<never>', nestedInArrayShape($aNever));

	$returningNull = static fn () => null;
	$returningInt = static fn (): int => 1;

	assertType('NeverIntoUnionTemplate\\P<mixed>', tOrNull($returningNull));
	assertType('NeverIntoUnionTemplate\\P<mixed>', tOrIntFloat($returningInt));
}
