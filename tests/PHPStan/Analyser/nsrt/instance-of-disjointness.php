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
}
