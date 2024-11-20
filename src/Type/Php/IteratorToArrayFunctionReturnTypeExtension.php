<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use function strtolower;

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
		$arrayKeyType = $traversableType->getIterableKeyType()->toArrayKey();

		if ($arrayKeyType instanceof ErrorType) {
			return new NeverType(true);
		}

		$isList = false;

		if (isset($arguments[1])) {
			$preserveKeysType = $scope->getType($arguments[1]->value);

			if ($preserveKeysType->isFalse()->yes()) {
				$arrayKeyType = new IntegerType();
				$isList = true;
			}
		}

		$arrayType = new ArrayType(
			$arrayKeyType,
			$traversableType->getIterableValueType(),
		);

		if ($isList) {
			$arrayType = AccessoryArrayListType::intersectWith($arrayType);
		}

		return $arrayType;
	}

}
