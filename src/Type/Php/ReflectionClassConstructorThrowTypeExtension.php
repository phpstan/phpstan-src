<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
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
		foreach (TypeUtils::flattenTypes($valueType) as $type) {
			if ($type instanceof ClassStringType || $type instanceof ObjectWithoutClassType || $type instanceof ObjectType) {
				continue;
			}

			if (
				$type instanceof ConstantStringType
				&& $this->reflectionProvider->hasClass($type->getValue())
			) {
				continue;
			}

			return $methodReflection->getThrowType();
		}

		return null;
	}

}
