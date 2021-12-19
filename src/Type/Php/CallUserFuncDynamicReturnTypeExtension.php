<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function array_slice;

class CallUserFuncDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'call_user_func';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$args = $functionCall->getArgs();
		$defaultReturn = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();

		$callbackType = $scope->getType($args[0]->value);
		if ($callbackType->isCallable()->yes()) {
			return $scope->getType(new FuncCall($args[0]->value, array_slice($args, 1)));
		}

		return $defaultReturn;
	}

}
