<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * Narrows ReflectionEnum::getCase() to ReflectionEnumBackedCase once getBackingType()
 * is known to be non-null (e.g. after isBacked()), which the conditional return type
 * cannot express when the template type of ReflectionEnum is invariant.
 */
#[AutowiredService]
final class ReflectionEnumGetCaseDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return 'ReflectionEnum';
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getCase';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$backingType = $scope->getType(new MethodCall($methodCall->var, new Identifier('getBackingType')));
		if (!$backingType->isNull()->no()) {
			return null;
		}

		return new ObjectType('ReflectionEnumBackedCase');
	}

}
