<?php declare(strict_types = 1);

namespace PHPStan;

use Iterator;

final class IterativeTrinary
{

	/**
	 * @param Iterator<TrinaryLogic> $it
	 */
	public static function and(Iterator $it): TrinaryLogic
	{
		$trinaries = [];
		foreach ($it as $trinary) {
			if (!$trinary->yes()) {
				return $trinary;
			}
			$trinaries[] = $trinary;
		}

		return TrinaryLogic::createYes()->and(...$trinaries);
	}

	/**
	 * @param Iterator<TrinaryLogic> $it
	 */
	public static function or(Iterator $it): TrinaryLogic
	{
		$trinaries = [];

		foreach ($it as $trinary) {
			if ($trinary->yes()) {
				return $trinary;
			}
			$trinaries[] = $trinary;
		}

		return TrinaryLogic::createNo()->or(...$trinaries);
	}

}
