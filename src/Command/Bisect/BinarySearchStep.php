<?php declare(strict_types = 1);

namespace PHPStan\Command\Bisect;

/**
 * @template T
 */
final class BinarySearchStep
{

	/**
	 * @param T $item Item to test
	 * @param list<T> $ifGood Remaining items to search if this item is good
	 * @param list<T> $ifBad Remaining items to search if this item is bad
	 */
	public function __construct(
		public readonly mixed $item,
		public readonly array $ifGood,
		public readonly array $ifBad,
		public readonly int $stepsRemaining,
	)
	{
	}

}
