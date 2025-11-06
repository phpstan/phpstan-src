<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use function strtolower;

#[AutowiredService]
final class IteratorToArrayFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'iterator_to_array';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$arguments = $functionCall->getArgs();

		if ($arguments === []) {
			return null;
		}

		$traversableType = $scope->getType($arguments[0]->value);

		if (isset($arguments[1])) {
			$preserveKeysType = $scope->getType($arguments[1]->value);

			if ($preserveKeysType->isFalse()->yes()) {
				return new IntersectionType([new ArrayType(
					IntegerRangeType::createAllGreaterThanOrEqualTo(0),
					$traversableType->getIterableValueType(),
				), new AccessoryArrayListType()]);
			}
		}

		$arrayKeyType = $traversableType->getIterableKeyType()->toArrayKey();

		if ($arrayKeyType->isError()->yes()) {
			return new NeverType(true);
		}

		return new ArrayType(
			$arrayKeyType,
			$traversableType->getIterableValueType(),
		);
	}

}
