<?php declare(strict_types = 1);

namespace InvariantMutualSubtypes;

/** @template T */
class Box
{

}

/** @param \ArrayObject<int, iterable<int>|object> $a */
function takeArrayObject(\ArrayObject $a): void
{

}

/** @param Box<iterable<int>|object> $box */
function takeBox(Box $box): void
{

}

/**
 * @param \ArrayObject<int, array<int>|object> $mutual
 * @param Box<array<int>|object> $mutualBox
 * @param Box<array<int>> $narrower
 */
function test(\ArrayObject $mutual, Box $mutualBox, Box $narrower): void
{
	// each argument is a subtype of the other - the same type for an invariant template
	takeArrayObject($mutual);
	takeBox($mutualBox);

	// a proper subtype is still not accepted
	takeBox($narrower);
}
