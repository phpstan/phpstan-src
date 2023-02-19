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
use ReflectionMethod;
use function count;

class ReflectionMethodConstructorThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct' && $methodReflection->getDeclaringClass()->getName() === ReflectionMethod::class;
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) < 2) {
			return $methodReflection->getThrowType();
		}

		$valueType = $scope->getType($methodCall->getArgs()[0]->value);
		$propertyType = $scope->getType($methodCall->getArgs()[1]->value);
		foreach (TypeUtils::flattenTypes($valueType) as $type) {
			$classes = $type->getClassStringObjectType()->getObjectClassNames();
			if (count($classes) === 0) {
				return $methodReflection->getThrowType();
			}

			foreach ($classes as $class) {
				$classReflection = $this->reflectionProvider->getClass($class);
				foreach ($propertyType->getConstantStrings() as $constantPropertyString) {
					if (!$classReflection->hasMethod($constantPropertyString->getValue())) {
						return $methodReflection->getThrowType();
					}
				}
			}

			$valueType = TypeCombinator::remove($valueType, $type);
		}

		if (!$valueType instanceof NeverType) {
			return $methodReflection->getThrowType();
		}

		// Look for non constantStrings value.
		foreach ($propertyType->getConstantStrings() as $constantPropertyString) {
			$propertyType = TypeCombinator::remove($propertyType, $constantPropertyString);
		}

		if (!$propertyType instanceof NeverType) {
			return $methodReflection->getThrowType();
		}

		return null;
	}

}
