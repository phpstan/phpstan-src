<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\TrinaryLogic;
use function array_keys;

/**
 * Merges the per-parameter @pure-unless-callable-is-impure flags of several
 * method reflections (union/intersection members). A parameter that is flagged
 * differently across the members - or present in some and absent in others -
 * resolves to Maybe.
 */
final class MergedPureUnlessCallableIsImpureParameters
{

	/**
	 * @param ExtendedMethodReflection[] $methods
	 * @return array<string, TrinaryLogic>
	 */
	public static function merge(array $methods): array
	{
		$parameterNames = [];
		$maps = [];
		foreach ($methods as $method) {
			$map = $method->getPureUnlessCallableIsImpureParameters();
			$maps[] = $map;
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
