<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPStan\Turbo\ReferencedByTurboExtension;
use function array_pop;

/** @internal */
#[ReferencedByTurboExtension(key: 'iterableHelper')]
final class IterableHelper
{

	/**
	 * @param array<iterable<mixed>> $iterables
	 * @return iterable<list<mixed>>
	 */
	public static function combinations(array $iterables): iterable
	{
		if ($iterables === []) {
			yield [];
			return;
		}

		$last = array_pop($iterables);

		foreach (self::combinations($iterables) as $combination) {
			foreach ($last as $value) {
				$next = $combination;
				$next[] = $value;
				yield $next;
			}
		}
	}

	/**
	 * @template T
	 * @param list<T> $values
	 * @return iterable<T>
	 */
	public static function yieldValues(array $values): iterable
	{
		yield from $values;
	}

}
