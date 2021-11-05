<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class CallUserFuncDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['call_user_func_array', 'call_user_func'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$args = $functionCall->getArgs();
		$defaultReturn = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();

		if ($functionReflection->getName() === 'call_user_func_array' && count($args) <= 1) {
			return new NeverType();
		}

		$callbackType = $scope->getType($args[0]->value);
		if ($callbackType->isCallable()->yes()) {
			$returnType = $scope->getType(new FuncCall($args[0]->value, array_slice($args, 1)));

			return new UnionType([$returnType, new ConstantBooleanType(false)]);
		}

		return $defaultReturn;
	}

}
