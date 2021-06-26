<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use ReflectionClass;

class ReflectionClassConstructorThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	private ReflectionProvider $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct' && $methodReflection->getDeclaringClass()->getName() === ReflectionClass::class;
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->args) < 1) {
			return $methodReflection->getThrowType();
		}

		$valueType = $scope->getType($methodCall->args[0]->value);
		foreach (TypeUtils::getConstantStrings($valueType) as $constantString) {
			if (!$this->reflectionProvider->hasClass($constantString->getValue())) {
				return $methodReflection->getThrowType();
			}

			$valueType = TypeCombinator::remove($valueType, $constantString);
		}

		if ((new ObjectWithoutClassType())->isSuperTypeOf($valueType)->yes()) {
			return null;
		}

		if (!$valueType instanceof NeverType) {
			return $methodReflection->getThrowType();
		}

		return null;
	}

}
