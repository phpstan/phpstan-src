<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPStan\Turbo\ShadowedByTurboExtension;
use Traversable;
use function array_pop;

#[ShadowedByTurboExtension(turboClass: 'PHPStanTurbo\CombinationsHelper', implementation: __DIR__ . '/../../turbo-ext/src/CombinationsHelper.cpp')]
final class CombinationsHelper
{

	/**
	 * @param array<iterable<mixed>> $arrays
	 * @return Traversable<list<mixed>>
	 */
	public static function combinations(array $arrays): iterable
	{
		// from https://stackoverflow.com/a/70800936/565782 by Arnaud Le Blanc
		if ($arrays === []) {
			yield [];
			return;
		}

		$last = array_pop($arrays);

		foreach (self::combinations($arrays) as $combination) {
			foreach ($last as $elem) {
				$comb = $combination;
				$comb[] = $elem;
				yield $comb;
			}
		}
	}

}
