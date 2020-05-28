<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;

class CallUserFuncArrayDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'call_user_func_array';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$args = $functionCall->args;

		if (
			count($args) < 2
			|| !$args[1]->value instanceof Array_
		) {
			return $this->getNativeReturnType($functionReflection, $args, $scope);
		}

		$callback = $args[0]->value;
		$params = $this->prepareParamsForFuncCall($args[1]->value);

		$callableType = $scope->getType($callback);

		if ($callableType->isCallable()->no()) {
			return $this->getNativeReturnType($functionReflection, $params, $scope);
		}

		return ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$params,
			$callableType->getCallableParametersAcceptors($scope)
		)->getReturnType();
	}

	/**
	 * @param array<Arg> $params
	 */
	private function getNativeReturnType(FunctionReflection $functionReflection, array $params, Scope $scope): Type
	{
		return ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$params,
			$functionReflection->getVariants()
		)->getReturnType();
	}

	/**
	 * @return array<Arg>
	 */
	private function prepareParamsForFuncCall(Array_ $paramsArg): array
	{
		$result = [];

		foreach ($paramsArg->items as $item) {
			$result[] = new Arg($item->value, $item->byRef, $item->unpack);
		}

		return $result;
	}

}
