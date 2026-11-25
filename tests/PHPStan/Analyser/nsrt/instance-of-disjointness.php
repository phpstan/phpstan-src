<?php declare(strict_types = 1);

namespace InstanceOfDisjointness;

use function PHPStan\Testing\assertType;

class Base
{

}

final class FinalChild extends Base implements \Countable
{

	public function count(): int
	{
		return 0;
	}

}

final class FinalOther
{

}

/**
 * @param iterable<int> $iterable
 * @param list<int> $list
 * @param list{int, int} $pair
 * @param array{object, string} $objectPair
 * @param array{int|object, string} $maybeObjectPair
 * @param array{int|string, string} $maybeStringPair
 * @param array{object, int|string} $maybeMethodPair
 * @param array{object, int} $intMethodPair
 */
function test(
	\stdClass $std,
	iterable $iterable,
	\Exception $exception,
	Base $base,
	\Countable $countable,
	FinalOther $finalOther,
	array $list,
	array $pair,
	array $objectPair,
	array $maybeObjectPair,
	array $maybeStringPair,
	array $maybeMethodPair,
	array $intMethodPair,
): void
{
	// a subclass of stdClass may implement Traversable
	if (is_iterable($std)) {
		assertType('iterable&stdClass', $std);
	}
	if ($iterable instanceof \stdClass) {
		assertType('iterable<int>&stdClass', $iterable);
	}
	if (is_iterable($exception)) {
		assertType('Exception&iterable', $exception);
	}
	// a final class implementing the interface may be passed
	if (is_countable($countable)) {
		assertType('Countable', $countable);
	}
	if (is_iterable($finalOther)) {
		assertType('*NEVER*', $finalOther);
	}

	// callable arrays need an object or a string and a method name
	if (is_callable($list)) {
		assertType('*NEVER*', $list);
	}
	if (is_callable($pair)) {
		assertType('*NEVER*', $pair);
	}
	if (is_callable($objectPair)) {
		assertType('list{object, string}&callable(): mixed', $objectPair);
	}
	if (is_callable($maybeObjectPair)) {
		assertType('list{int|object, string}&callable(): mixed', $maybeObjectPair);
	}
	if (is_callable($maybeStringPair)) {
		assertType('list{int|string, string}&callable(): mixed', $maybeStringPair);
	}
	if (is_callable($maybeMethodPair)) {
		assertType('list{object, int|string}&callable(): mixed', $maybeMethodPair);
	}
	if (is_callable($intMethodPair)) {
		assertType('*NEVER*', $intMethodPair);
	}
}
