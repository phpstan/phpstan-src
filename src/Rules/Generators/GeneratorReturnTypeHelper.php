<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generators;

use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

final class GeneratorReturnTypeHelper
{

	/**
	 * Extracts the part of a generator function's declared return type that can
	 * actually hold the yielded Generator. Nullable or union return types like
	 * ?Generator or Iterator|float are valid in PHP, but their non-iterable parts
	 * (null, float, ...) would otherwise poison getIterableKeyType()/getIterableValueType()
	 * with ErrorType and silently disable yield key/value type checks.
	 */
	public static function getGeneratorType(Type $returnType): Type
	{
		$iterableTypes = [];
		foreach (TypeUtils::flattenTypes($returnType) as $innerType) {
			if ($innerType->isIterable()->no() || $innerType->isArray()->yes()) {
				continue;
			}

			$iterableTypes[] = $innerType;
		}

		if ($iterableTypes === []) {
			return $returnType;
		}

		return TypeCombinator::union(...$iterableTypes);
	}

}
