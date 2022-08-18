<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;

final class ArraySearchFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_search';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$argsCount = count($functionCall->getArgs());
		if ($argsCount < 2) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$haystackArgType = $scope->getType($functionCall->getArgs()[1]->value);
		$haystackIsArray = $haystackArgType->isArray();
		if ($haystackIsArray->no()) {
			return new NullType();
		}

		if ($argsCount < 3) {
			return TypeCombinator::union($haystackArgType->getIterableKeyType(), new ConstantBooleanType(false));
		}

		$strictArgType = $scope->getType($functionCall->getArgs()[2]->value);
		if (!$strictArgType instanceof ConstantBooleanType || $strictArgType->getValue() === false) {
			return TypeCombinator::union($haystackArgType->getIterableKeyType(), new ConstantBooleanType(false));
		}

		$needleArgType = $scope->getType($functionCall->getArgs()[0]->value);
		if ($haystackArgType->getIterableValueType()->isSuperTypeOf($needleArgType)->no()) {
			return new ConstantBooleanType(false);
		}

		$typesFromConstantArrays = [];
		if ($haystackIsArray->maybe()) {
			$typesFromConstantArrays[] = new NullType();
		}

		$haystackArrays = TypeUtils::getAnyArrays($haystackArgType);
		if (count($haystackArrays) === 0) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$arrays = [];
		$typesFromConstantArraysCount = 0;
		foreach ($haystackArrays as $haystackArray) {
			if (!$haystackArray instanceof ConstantArrayType) {
				$arrays[] = $haystackArray;
				continue;
			}

			$typesFromConstantArrays[] = $this->resolveTypeFromConstantHaystackAndNeedle($needleArgType, $haystackArray);
			$typesFromConstantArraysCount++;
		}

		if (
			$typesFromConstantArraysCount > 0
			&& count($haystackArrays) === $typesFromConstantArraysCount
		) {
			return TypeCombinator::union(...$typesFromConstantArrays);
		}

		$iterableKeyType = TypeCombinator::union(...$arrays)->getIterableKeyType();

		return TypeCombinator::union(
			$iterableKeyType,
			new ConstantBooleanType(false),
			...$typesFromConstantArrays,
		);
	}

	private function resolveTypeFromConstantHaystackAndNeedle(Type $needle, ConstantArrayType $haystack): Type
	{
		$matchesByType = [];

		foreach ($haystack->getValueTypes() as $index => $valueType) {
			$isNeedleSuperType = $valueType->isSuperTypeOf($needle);
			if ($isNeedleSuperType->no()) {
				$matchesByType[] = new ConstantBooleanType(false);
				continue;
			}

			if ($needle instanceof ConstantScalarType && $valueType instanceof ConstantScalarType
				&& $needle->getValue() === $valueType->getValue()
			) {
				return $haystack->getKeyTypes()[$index];
			}

			$matchesByType[] = $haystack->getKeyTypes()[$index];
			if (!$isNeedleSuperType->maybe()) {
				continue;
			}

			$matchesByType[] = new ConstantBooleanType(false);
		}

		if (count($matchesByType) > 0) {
			if (
				$haystack->getIterableValueType()->accepts($needle, true)->yes()
				&& $needle->isSuperTypeOf(new ObjectWithoutClassType())->no()
			) {
				return TypeCombinator::union(...$matchesByType);
			}

			return TypeCombinator::union(new ConstantBooleanType(false), ...$matchesByType);
		}

		return new ConstantBooleanType(false);
	}

}
