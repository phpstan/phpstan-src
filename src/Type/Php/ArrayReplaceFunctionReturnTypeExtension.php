<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function strtolower;

final class ArrayReplaceFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'array_replace';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$arrayTypes = $this->collectArrayTypes($functionCall, $scope);

		if (count($arrayTypes) === 0) {
			return null;
		}

		return $this->getResultType(...$arrayTypes);
	}

	private function getResultType(Type ...$arrayTypes): Type
	{
		$keyTypes = [];
		$valueTypes = [];
		$nonEmptyArray = false;
		foreach ($arrayTypes as $arrayType) {
			if (!$nonEmptyArray && $arrayType->isIterableAtLeastOnce()->yes()) {
				$nonEmptyArray = true;
			}

			$keyTypes[] = $arrayType->getIterableKeyType();
			$valueTypes[] = $arrayType->getIterableValueType();
		}

		$keyType = TypeCombinator::union(...$keyTypes);
		$valueType = TypeCombinator::union(...$valueTypes);

		$arrayType = new ArrayType($keyType, $valueType);
		return $nonEmptyArray ? TypeCombinator::intersect($arrayType, new NonEmptyArrayType()) : $arrayType;
	}

	/**
	 * @return Type[]
	 */
	private function collectArrayTypes(FuncCall $functionCall, Scope $scope): array
	{
		$args = $functionCall->getArgs();

		$arrayTypes = [];
		foreach ($args as $arg) {
			$argType = $scope->getType($arg->value);
			if (!$argType->isArray()->yes()) {
				continue;
			}

			$arrayTypes[] = $arg->unpack ? $argType->getIterableValueType() : $argType;
		}

		return $arrayTypes;
	}

}
