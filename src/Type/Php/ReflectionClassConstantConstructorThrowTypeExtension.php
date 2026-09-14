<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionClassConstant;
use function count;

#[AutowiredService]
final class ReflectionClassConstantConstructorThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct' && $methodReflection->getDeclaringClass()->getName() === ReflectionClassConstant::class;
	}

	public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) < 2) {
			return $methodReflection->getThrowType();
		}

		$valueType = $scope->getType($methodCall->getArgs()[0]->value);
		$constantType = $scope->getType($methodCall->getArgs()[1]->value);
		foreach ($valueType->getConstantStrings() as $constantString) {
			if (!$this->reflectionProvider->hasClass($constantString->getValue())) {
				return $methodReflection->getThrowType();
			}

			$classReflection = $this->reflectionProvider->getClass($constantString->getValue());
			foreach ($constantType->getConstantStrings() as $constantConstantString) {
				if (!$classReflection->hasConstant($constantConstantString->getValue())) {
					return $methodReflection->getThrowType();
				}
			}

			$valueType = TypeCombinator::remove($valueType, $constantString);
		}

		if (!$valueType instanceof NeverType) {
			return $methodReflection->getThrowType();
		}

		// Look for non constantStrings value.
		foreach ($constantType->getConstantStrings() as $constantConstantString) {
			$constantType = TypeCombinator::remove($constantType, $constantConstantString);
		}

		if (!$constantType instanceof NeverType) {
			return $methodReflection->getThrowType();
		}

		return null;
	}

}
