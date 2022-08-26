<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_slice;
use function count;

class ArrayIntersectKeyFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_intersect_key';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return ParametersAcceptorSelector::selectFromArgs($scope, $args, $functionReflection->getVariants())->getReturnType();
		}

		$argTypes = [];
		foreach ($args as $arg) {
			$argType = $scope->getType($arg->value);
			if ($arg->unpack) {
				$argTypes[] = $argType->getIterableValueType();
				continue;
			}

			$argTypes[] = $argType;
		}

		$firstArray = $argTypes[0];
		$otherArrays = array_slice($argTypes, 1);
		if (count($otherArrays) === 0) {
			return $firstArray;
		}

		$constantArrays = TypeUtils::getConstantArrays($firstArray);
		if (count($constantArrays) === 0) {
			return new ArrayType($firstArray->getIterableKeyType(), $firstArray->getIterableValueType());
		}

		$otherArraysType = TypeCombinator::union(...$otherArrays);
		$results = [];
		foreach ($constantArrays as $constantArray) {
			$builder = ConstantArrayTypeBuilder::createEmpty();
			foreach ($constantArray->getKeyTypes() as $i => $keyType) {
				$valueType = $constantArray->getValueTypes()[$i];
				$has = $otherArraysType->hasOffsetValueType($keyType);
				if ($has->no()) {
					continue;
				}
				$builder->setOffsetValueType($keyType, $valueType, !$has->yes());
			}
			$results[] = $builder->getArray();
		}

		return TypeCombinator::union(...$results);
	}

}
