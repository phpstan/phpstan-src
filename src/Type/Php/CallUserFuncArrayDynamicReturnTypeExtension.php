<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;

class CallUserFuncArrayDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'call_user_func_array';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$args = $functionCall->getArgs();
		$defaultReturn = ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();

		if (count($args) <= 1) {
			return new NeverType();
		}

		$callbackType = $scope->getType($args[0]->value);
		$callbackArgsType = $scope->getType($args[1]->value);

		if ($callbackType->isCallable()->yes() && $callbackArgsType->isArray()->yes()) {
			$callbackArgs = [];
			if ($args[1]->value instanceof Array_) {
				foreach ($args[1]->value->items as $item) {
					if ($item === null) {
						continue;
					}
					$callbackArgs[] = new Arg($item);
				}
			} elseif ($args[1]->value instanceof Variable) {
				$arg = new Arg($args[1]->value, $args[1]->byRef, true, $args[1]->getAttributes(), $args[1]->name);
				$callbackArgs[] = $arg;
			}
			return $scope->getType(new FuncCall($args[0]->value, $callbackArgs));
		}

		return $defaultReturn;
	}

}
