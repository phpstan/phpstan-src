<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\TrinaryLogic;
use function array_keys;
use function array_map;

/**
 * Merges the per-parameter conditional purity flags of several method reflections
 * (union/intersection members). A parameter that is flagged differently across the
 * members - or present in some and absent in others - resolves to Maybe.
 */
final class MergedConditionalPurityParameters
{

	/**
	 * @param ExtendedMethodReflection[] $methods
	 * @return array<string, TrinaryLogic>
	 */
	public static function mergePureUnlessCallableIsImpure(array $methods): array
	{
		return self::merge(array_map(
			static fn (ExtendedMethodReflection $method) => $method->getPureUnlessCallableIsImpureParameters(),
			$methods,
		));
	}

	/**
	 * @param ExtendedMethodReflection[] $methods
	 * @return array<string, TrinaryLogic>
	 */
	public static function mergePureUnlessParameterPassed(array $methods): array
	{
		return self::merge(array_map(
			static fn (ExtendedMethodReflection $method) => $method->getPureUnlessParameterPassedParameters(),
			$methods,
		));
	}

	/**
	 * @param array<array<string, TrinaryLogic>> $maps
	 * @return array<string, TrinaryLogic>
	 */
	private static function merge(array $maps): array
	{
		$parameterNames = [];
		foreach ($maps as $map) {
			foreach (array_keys($map) as $name) {
				$parameterNames[$name] = true;
			}
		}

		$merged = [];
		foreach (array_keys($parameterNames) as $name) {
			$value = null;
			foreach ($maps as $map) {
				$current = $map[$name] ?? TrinaryLogic::createNo();
				if ($value === null) {
					$value = $current;
					continue;
				}
				$value = $value->equals($current) ? $value : TrinaryLogic::createMaybe();
			}

			if ($value === null) {
				continue;
			}

			$merged[$name] = $value;
		}

		return $merged;
	}

}
