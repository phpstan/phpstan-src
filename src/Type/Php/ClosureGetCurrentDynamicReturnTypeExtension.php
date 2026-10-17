<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Closure;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/ClosureGetCurrentDynamicReturnTypeExtension.cpp')]
final class ClosureGetCurrentDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return Closure::class;
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getCurrent';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
	{
		if (!$scope->isInAnonymousFunction()) {
			return new NeverType();
		}

		return $scope->getAnonymousFunctionReflection();
	}

}
