<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use PHPStan\Turbo\ShadowedByTurboExtension;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/CombinationsHelper.cpp')]
final class CombinationsHelper
{

	/**
	 * @param array<iterable<mixed>> $arrays
	 * @return iterable<list<mixed>>
	 */
	public static function combinations(array $arrays): iterable
	{
		return IterableHelper::combinations($arrays);
	}

}
