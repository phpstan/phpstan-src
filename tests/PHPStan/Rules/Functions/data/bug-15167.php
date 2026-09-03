<?php declare(strict_types = 1);

namespace Bug15167Functions;

use Traversable;

/** @template-covariant T */
interface P
{

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

function test(): void
{
	$throwing = static function (): void {
		throw new \Exception();
	};

	arrayOrT($throwing);
	iterableOrT($throwing);
	traversableOrT($throwing);
	pOrT($throwing);
}
