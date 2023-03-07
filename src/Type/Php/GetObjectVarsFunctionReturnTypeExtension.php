<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class GetObjectVarsFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'get_object_vars';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();

		if (count($args) === 0) {
			return null;
		}

		$argType = $scope->getType($args[0]->value);

		if (!$argType->isObject()->yes()) {
			return null;
		}

		$objectTypes = $argType->getObjectClassReflections();

		if (count($objectTypes) === 0) {
			return null;
		}

		$types = [];
		foreach ($objectTypes as $objectType) {
			$keys = [];
			$values = [];
			foreach ($objectType->getPropertyNames() as $propertyName) {
				$property = $objectType->getProperty(
					$propertyName,
					$scope,
				);

				if (!$scope->canAccessProperty($property)) {
					continue;
				}

				$keys[] = new ConstantStringType($propertyName);
				$values[] = $property->getReadableType();
			}

			$types[] = new ConstantArrayType(
				$keys,
				$values,
			);
		}

		return TypeCombinator::union(
			...$types,
		);
	}

}
