<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Iterator;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function in_array;

#[AutowiredService]
final class IteratorCurrentReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return Iterator::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), ['current', 'key'], true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		$returnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$methodCall->getArgs(),
			$methodReflection->getVariants(),
		)->getReturnType();

		return TypeCombinator::addNull($returnType);
	}

}
