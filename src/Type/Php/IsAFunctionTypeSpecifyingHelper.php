<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Type;

#[AutowiredService]
final class IsAFunctionTypeSpecifyingHelper
{

	public function determineType(
		Type $objectOrClassType,
		Type $classType,
		bool $allowString,
		bool $allowSameClass,
	): ?Type
	{
		$result = $classType->toObjectTypeForIsACheck($objectOrClassType, $allowString, $allowSameClass);

		// `getConstantStrings() === []` propagates uncertainty from
		// the input as a whole — preserved from the original
		// `$isUncertain` initial state to keep the false-positive
		// suppression below identical.
		$isUncertain = $result->uncertainty || $classType->getConstantStrings() === [];

		// prevent false-positives
		if ($isUncertain && $result->type->isSuperTypeOf($objectOrClassType)->yes()) {
			return null;
		}

		return $result->type;
	}

}
