<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

class ArrayReduceFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_reduce';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (!isset($functionCall->getArgs()[1])) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$callbackType = $scope->getType($functionCall->getArgs()[1]->value);
		if ($callbackType->isCallable()->no()) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$callbackReturnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$callbackType->getCallableParametersAcceptors($scope),
		)->getReturnType();

		if (isset($functionCall->getArgs()[2])) {
			$initialType = $scope->getType($functionCall->getArgs()[2]->value);
		} else {
			$initialType = new NullType();
		}

		$arraysType = $scope->getType($functionCall->getArgs()[0]->value);
		$constantArrays = TypeUtils::getConstantArrays($arraysType);
		if ($constantArrays !== []) {
			$onlyEmpty = true;
			$onlyNonEmpty = true;
			foreach ($constantArrays as $constantArray) {
				$isEmpty = $constantArray->getValueTypes() === [];
				$onlyEmpty = $onlyEmpty && $isEmpty;
				$onlyNonEmpty = $onlyNonEmpty && !$isEmpty;
			}

			if ($onlyEmpty) {
				return $initialType;
			}
			if ($onlyNonEmpty) {
				return $callbackReturnType;
			}
		}

		return TypeCombinator::union($callbackReturnType, $initialType);
	}

}
