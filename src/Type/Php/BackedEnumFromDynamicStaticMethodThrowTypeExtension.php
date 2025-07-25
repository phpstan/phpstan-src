<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VoidType;
use function count;

#[AutowiredService]
final class BackedEnumFromDynamicStaticMethodThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'from'
			&& $methodReflection->getDeclaringClass()->isBackedEnum();
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		$arguments = $methodCall->getArgs();
		if (count($arguments) < 1) {
			return $methodReflection->getThrowType();
		}

		$valueType = $scope->getType($arguments[0]->value);
		if (!$valueType->isConstantScalarValue()->yes()) {
			return $methodReflection->getThrowType();
		}

		$enumCases = $methodReflection->getDeclaringClass()->getEnumCases();

		$backingValueTypes = [];
		foreach ($enumCases as $enumCase) {
			if ($enumCase->getBackingValueType() === null) {
				return $methodReflection->getThrowType();
			}

			$backingValueTypes[] = $enumCase->getBackingValueType();
		}

		$backingValueType = TypeCombinator::union(...$backingValueTypes);
		if (!$backingValueType->isSuperTypeOf($valueType)->yes()) {
			return $methodReflection->getThrowType();
		}

		return new VoidType();
	}

}
