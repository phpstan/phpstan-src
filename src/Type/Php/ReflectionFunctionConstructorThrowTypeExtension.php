<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use ReflectionFunction;
use function count;

class ReflectionFunctionConstructorThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	private ReflectionProvider $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct' && $methodReflection->getDeclaringClass()->getName() === ReflectionFunction::class;
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) < 1) {
			return $methodReflection->getThrowType();
		}

		$valueType = $scope->getType($methodCall->getArgs()[0]->value);
		foreach (TypeUtils::getConstantStrings($valueType) as $constantString) {
			if (!$this->reflectionProvider->hasFunction(new Name($constantString->getValue()), $scope)) {
				return $methodReflection->getThrowType();
			}

			$valueType = TypeCombinator::remove($valueType, $constantString);
		}

		if (!$valueType instanceof NeverType) {
			return $methodReflection->getThrowType();
		}

		return null;
	}

}
