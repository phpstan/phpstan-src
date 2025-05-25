<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;

#[AutowiredService]
final class ArrayFilterFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private ArrayFilterFunctionReturnTypeHelper $arrayFilterFunctionReturnTypeHelper)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_filter';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$arrayArg = $functionCall->getArgs()[0]->value ?? null;
		$callbackArg = $functionCall->getArgs()[1]->value ?? null;
		$flagArg = $functionCall->getArgs()[2]->value ?? null;

		return $this->arrayFilterFunctionReturnTypeHelper->getType($scope, $arrayArg, $callbackArg, $flagArg);
	}

}
