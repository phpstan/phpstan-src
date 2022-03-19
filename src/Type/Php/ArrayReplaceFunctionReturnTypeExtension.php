<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function strtolower;

class ArrayReplaceFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'array_replace';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$args = $functionCall->getArgs();

		if (count($args) < 1) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$arrayType = $scope->getType($args[0]->value);
		if ($arrayType->isArray()->yes()) {
			$resultType = $this->getResultType($functionCall, $scope);

			if ($resultType !== null) {
				if ($this->returnsNonEmptyArray($functionCall, $scope)) {
					$resultType = TypeCombinator::intersect($resultType, new NonEmptyArrayType());
				}

				return $resultType;
			}
		}

		return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
	}

	private function getResultType(FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();

		$arrayType = $scope->getType($args[0]->value);
		$keyTypes = [$arrayType->getIterableKeyType()];
		$valueTypes = [$arrayType->getIterableValueType()];

		for ($i = 1; $i < count($args); $i++) {
			$replaceArray = $scope->getType($args[$i]->value);

			if (!$replaceArray->isArray()->yes()) {
				return null;
			}

			$keyTypes[] = $replaceArray->getIterableKeyType();
			$valueTypes[] = $replaceArray->getIterableValueType();
		}

		$keyType = TypeCombinator::union(...$keyTypes);
		$valueType = TypeCombinator::union(...$valueTypes);

		return new ArrayType($keyType, $valueType);
	}

	private function returnsNonEmptyArray(FuncCall $functionCall, Scope $scope): bool
	{
		$args = $functionCall->getArgs();

		for ($i = 0; $i < count($args); $i++) {
			$array = $scope->getType($args[$i]->value);

			if (!$array->isArray()->yes()) {
				continue;
			}

			if ($array->isIterableAtLeastOnce()->yes()) {
				return true;
			}
		}

		return false;
	}

}
