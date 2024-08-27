<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use BackedEnum;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

final class BackedEnumFromMethodDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
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

		if (count($valueType->getConstantScalarValues()) === 0) {
			return null;
		}

		$resultEnumCases = [];
		$addNull = false;
		foreach ($valueType->getConstantScalarValues() as $value) {
			$hasMatching = false;
			foreach ($enumCases as $enumCase) {
				if ($enumCase->getBackingValueType() === null) {
					continue;
				}

				$enumCaseValues = $enumCase->getBackingValueType()->getConstantScalarValues();
				if (count($enumCaseValues) !== 1) {
					continue;
				}

				if ($value === $enumCaseValues[0]) {
					$resultEnumCases[] = new EnumCaseObjectType($enumCase->getDeclaringEnum()->getName(), $enumCase->getName(), $enumCase->getDeclaringEnum());
					$hasMatching = true;
					break;
				}
			}

			if ($hasMatching) {
				continue;
			}

			$addNull = true;
		}

		if (count($resultEnumCases) === 0) {
			if ($methodReflection->getName() === 'tryFrom') {
				return new NullType();
			}

			return null;
		}

		$result = TypeCombinator::union(...$resultEnumCases);
		if ($addNull && $methodReflection->getName() === 'tryFrom') {
			return TypeCombinator::addNull($result);
		}

		return $result;
	}

}
