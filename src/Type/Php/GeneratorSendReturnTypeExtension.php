<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Generator;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

#[AutowiredService]
final class GeneratorSendReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return Generator::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'send';
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
