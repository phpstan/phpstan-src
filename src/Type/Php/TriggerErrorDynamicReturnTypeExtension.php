<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;

class TriggerErrorDynamicReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'trigger_error';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->getArgs()) < 2) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$errorType = $scope->getType($functionCall->getArgs()[1]->value);
		if ($errorType instanceof ConstantScalarType) {
			if ($errorType->getValue() === E_USER_ERROR) {
				return new NeverType();
			}
		}

		return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
	}

}
