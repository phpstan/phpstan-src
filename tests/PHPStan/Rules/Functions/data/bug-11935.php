<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11935;

/**
 * @template A
 *
 * @param mixed $a Intentionally mixed
 * @param callable(A): A $aa
 * @return A
 */
function identity(mixed $a, callable $aa): mixed
{
	return $aa($a);
}

/** @template T */
class Inv
{

}

/**
 * @template A
 *
 * @param Inv<mixed> $a Intentionally Inv<mixed>
 * @param callable(Inv<A>): Inv<A> $aa
 * @return Inv<A>
 */
function identityInv(Inv $a, callable $aa): Inv
{
	return $aa($a);
}

/**
 * @param Inv<mixed> $a
 * @param callable(Inv<int>): void $aa
 */
function passMixedTypeArgument(Inv $a, callable $aa): void
{
	$aa($a);
}
