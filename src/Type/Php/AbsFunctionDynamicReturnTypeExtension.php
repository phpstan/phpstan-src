<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/AbsFunctionDynamicReturnTypeExtension.cpp')]
final class AbsFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'abs';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();

		if (!isset($args[0])) {
			return null;
		}

		$inputType = $scope->getType($args[0]->value);

		$outputType = $inputType->toAbsoluteNumber();

		if ($outputType instanceof ErrorType) {
			return null;
		}

		return $outputType;
	}

}
