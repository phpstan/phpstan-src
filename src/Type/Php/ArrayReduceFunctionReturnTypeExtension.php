<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\TrinaryLogic;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

final class ArrayReduceFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_reduce';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (!isset($functionCall->getArgs()[1])) {
			return null;
		}

		$callbackType = $scope->getType($functionCall->getArgs()[1]->value);
		if ($callbackType->isCallable()->no()) {
			return null;
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
		$constantArrays = $arraysType->getConstantArrays();
		if (count($constantArrays) > 0) {
			$onlyEmpty = TrinaryLogic::createYes();
			$onlyNonEmpty = TrinaryLogic::createYes();
			foreach ($constantArrays as $constantArray) {
				$iterableAtLeastOnce = $constantArray->isIterableAtLeastOnce();
				$onlyEmpty = $onlyEmpty->and($iterableAtLeastOnce->negate());
				$onlyNonEmpty = $onlyNonEmpty->and($iterableAtLeastOnce);
			}

			if ($onlyEmpty->yes()) {
				return $initialType;
			}
			if ($onlyNonEmpty->yes()) {
				return $callbackReturnType;
			}
		}

		return TypeCombinator::union($callbackReturnType, $initialType);
	}

}
