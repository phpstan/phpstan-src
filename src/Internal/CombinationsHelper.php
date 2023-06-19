<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use function array_shift;

final class CombinationsHelper
{

	/**
	 * @param array<mixed> $arrays
	 * @return iterable<mixed>
	 */
	public static function combinations(array $arrays): iterable
	{
		// from https://stackoverflow.com/a/70800936/565782 by Arnaud Le Blanc
		if ($arrays === []) {
			yield [];
			return;
		}

		$head = array_shift($arrays);

		foreach ($head as $elem) {
			foreach (self::combinations($arrays) as $combination) {
				$comb = [$elem];
				foreach ($combination as $c) {
					$comb[] = $c;
				}
				yield $comb;
			}
		}
	}

}
