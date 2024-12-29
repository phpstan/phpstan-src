<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use ArrayAccess;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
final class ArrayAccessOffsetGetMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return ArrayAccess::class;
	}

	public function isMethodSupported(
		MethodReflection $methodReflection,
	): bool
	{
		return $methodReflection->getName() === 'offsetGet';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		if (count($methodCall->getArgs()) < 1) {
			return null;
		}
		$key = $methodCall->getArgs()[0]->value;
		$keyType = $scope->getType($key);
		$objectType = $scope->getType($methodCall->var);

		if (!$objectType->hasOffsetValueType($keyType)->yes()) {
			return null;
		}

		return $objectType->getOffsetValueType($keyType);
	}

}
