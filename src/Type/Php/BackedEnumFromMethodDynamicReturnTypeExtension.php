<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use BackedEnum;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function is_int;
use function is_string;

class BackedEnumFromMethodDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return BackedEnum::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), ['from', 'tryFrom'], true);
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (!$methodReflection->getDeclaringClass()->isBackedEnum()) {
			return null;
		}

		$arguments = $methodCall->getArgs();
		if (count($arguments) < 1) {
			return null;
		}

		$valueType = $scope->getType($arguments[0]->value);

		$enumCases = $methodReflection->getDeclaringClass()->getEnumCases();
		if (count($enumCases) === 0) {
			if ($methodReflection->getName() === 'tryFrom') {
				return new NullType();
			}

			return null;
		}

		$possibleConstantScalaValues = $valueType->getConstantScalarValues();

		if (count($possibleConstantScalaValues) === 0) {
			return null;
		}

		$backedEnumType = $methodReflection->getDeclaringClass()->getBackedEnumType();
		if ($backedEnumType === null) {
			return null;
		}

		$isIntEnum = (new IntegerType())->isSuperTypeOf($backedEnumType)->yes();
		$enumValueMap = [];
		foreach ($enumCases as $enumCase) {
			if ($enumCase->getBackingValueType() === null) {
				continue;
			}
			$enumCaseValues = $enumCase->getBackingValueType()->getConstantScalarValues();
			if (count($enumCaseValues) !== 1) {
				continue;
			}

			$enumValueMap[$enumCaseValues[0]] = $enumCase;
		}

		$resultEnumCases = [];
		$isUnmatchedValuePossible = false;
		foreach ($possibleConstantScalaValues as $value) {
			// This assumes declare(strict_types=1);
			if (($isIntEnum && ! is_int($value)) || (! $isIntEnum && ! is_string($value))) {
				$isUnmatchedValuePossible = true;

				continue;
			}

			$enumCase = $enumValueMap[$value] ?? null;

			if ($enumCase === null) {
				$isUnmatchedValuePossible = true;
			} else {
				$resultEnumCases[] = new EnumCaseObjectType($enumCase->getDeclaringEnum()->getName(), $enumCase->getName(), $enumCase->getDeclaringEnum());
			}
		}

		if (count($resultEnumCases) === 0) {
			if ($methodReflection->getName() === 'tryFrom') {
				return new NullType();
			}

			return null;
		}

		if ($isUnmatchedValuePossible && $methodReflection->getName() === 'tryFrom') {
			$resultEnumCases[] = new NullType();
		}

		return TypeCombinator::union(...$resultEnumCases);
	}

}
