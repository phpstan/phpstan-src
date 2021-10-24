<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use ReflectionProperty;

class ReflectionPropertyConstructorThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	private ReflectionProvider $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct' && $methodReflection->getDeclaringClass()->getName() === ReflectionProperty::class;
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) < 2) {
			return $methodReflection->getThrowType();
		}

		$valueType = $scope->getType($methodCall->getArgs()[0]->value);
		$propertyType = $scope->getType($methodCall->getArgs()[1]->value);
		foreach (TypeUtils::getConstantStrings($valueType) as $constantString) {
			if (!$this->reflectionProvider->hasClass($constantString->getValue())) {
				return $methodReflection->getThrowType();
			}

			$classReflection = $this->reflectionProvider->getClass($constantString->getValue());
			foreach (TypeUtils::getConstantStrings($propertyType) as $constantPropertyString) {
				if (!$classReflection->hasProperty($constantPropertyString->getValue())) {
					return $methodReflection->getThrowType();
				}
			}

			$valueType = TypeCombinator::remove($valueType, $constantString);
		}

		if (!$valueType instanceof NeverType) {
			return $methodReflection->getThrowType();
		}

		// Look for non constantStrings value.
		foreach (TypeUtils::getConstantStrings($propertyType) as $constantPropertyString) {
			$propertyType = TypeCombinator::remove($propertyType, $constantPropertyString);
		}

		if (!$propertyType instanceof NeverType) {
			return $methodReflection->getThrowType();
		}

		return null;
	}

}
